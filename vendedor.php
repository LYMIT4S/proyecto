<?php
session_start();
require 'db.php';
require('fpdf/fpdf.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'vendedor') {
    header('Location: login.php');
    exit();
}

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Obtener categorías y productos agrupados por categoría
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll();
$productosPorCategoria = [];

foreach ($categorias as $categoria) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE categoria_id = ?");
    $stmt->execute([$categoria['id']]);
    $productosPorCategoria[$categoria['nombre']] = $stmt->fetchAll();
}

// Procesar agregar al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_carrito'])) {
    $producto_id = $_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];
    
    if ($cantidad < 1) {
        $_SESSION['mensaje'] = "La cantidad debe ser al menos 1";
        header('Location: vendedor.php');
        exit();
    }
    
    // Verificar si el producto ya está en el carrito
    $encontrado = false;
    foreach ($_SESSION['carrito'] as &$item) {
        if ($item['id'] == $producto_id) {
            $item['cantidad'] += $cantidad;
            $encontrado = true;
            break;
        }
    }
    
    // Si no está en el carrito, agregarlo
    if (!$encontrado) {
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();
        
        if ($producto) {
            $_SESSION['carrito'][] = [
                'id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'cantidad' => $cantidad,
                'imagen' => $producto['imagen']
            ];
        }
    }
    
    $_SESSION['mensaje'] = "Producto agregado al carrito";
    header('Location: vendedor.php');
    exit();
}

// Procesar eliminar del carrito
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $index = $_GET['eliminar'];
    if (isset($_SESSION['carrito'][$index])) {
        array_splice($_SESSION['carrito'], $index, 1);
        $_SESSION['mensaje'] = "Producto eliminado del carrito";
    }
    header('Location: vendedor.php');
    exit();
}

// Procesar actualizar cantidad en carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_cantidad'])) {
    $index = $_POST['item_index'];
    $nueva_cantidad = (int)$_POST['cantidad'];
    
    if ($nueva_cantidad > 0 && isset($_SESSION['carrito'][$index])) {
        $_SESSION['carrito'][$index]['cantidad'] = $nueva_cantidad;
        $_SESSION['mensaje'] = "Cantidad actualizada";
    }
    header('Location: vendedor.php');
    exit();
}

// Procesar vaciar carrito
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    $_SESSION['mensaje'] = "Carrito vaciado";
    header('Location: vendedor.php');
    exit();
}

// Procesar generación de PDF con FPDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_ticket'])) {
    // Iniciar transacción para asegurar integridad de datos
    $pdo->beginTransaction();
    
    try {
        // Generar número de ticket único
        $numero_ticket = 'T-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $fecha = date('d/m/Y H:i:s');
        $total = 0;
        
        // 1. Registrar el pedido en la tabla pedidos
        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, fecha, estado) VALUES (?, NOW(), 'completado')");
        $stmt->execute([$_SESSION['user_id']]);
        $pedido_id = $pdo->lastInsertId();
        
        // 2. Registrar los detalles del pedido
        foreach ($_SESSION['carrito'] as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            $total += $subtotal;
            
            $stmt = $pdo->prepare("INSERT INTO detalles_pedido 
                                  (pedido_id, producto_id, cantidad, precio_unitario) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $pedido_id,
                $item['id'],
                $item['cantidad'],
                $item['precio']
            ]);
            
            // Actualizar stock del producto (opcional)
            $stmt = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['cantidad'], $item['id']]);
        }
        
        // Confirmar transacción
        $pdo->commit();
        
        // 3. Verificar/crear directorio tickets
        $ticketsDir = 'tickets';
        if (!file_exists($ticketsDir)) {
            if (!mkdir($ticketsDir, 0777, true)) {
                throw new Exception('No se pudo crear el directorio tickets');
            }
        }
        
        // 4. Verificar permisos
        if (!is_writable($ticketsDir)) {
            throw new Exception('El directorio tickets no tiene permisos de escritura');
        }
        
        // 5. Crear PDF (tamaño)
        $pdf = new FPDF('P', 'mm', array(150, 297));
        $pdf->AddPage();
        
        // Configurar márgenes
        $pdf->SetMargins(8, 8, 8);
        
        // Encabezado del ticket
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, 'Pablo & Daniel S.A de C.V', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'UAEM Valle de Mexico', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Telefono: 56-21-18-14-53', 0, 1, 'C');
        
        // Línea separadora gruesa
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, $pdf->GetY(), 140, $pdf->GetY());
        $pdf->Ln(8);
        
        // Información del ticket y pedido
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Ticket: ' . $numero_ticket, 0, 1);
        $pdf->Cell(0, 6, 'Pedido ID: ' . $pedido_id, 0, 1);
        $pdf->Cell(0, 6, 'Fecha: ' . $fecha, 0, 1);
        $pdf->Cell(0, 6, 'Vendedor: ' . htmlspecialchars($_SESSION['nombre']), 0, 1);
        
        // Línea separadora
        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, $pdf->GetY(), 140, $pdf->GetY());
        $pdf->Ln(8);
        
        // Encabezado de productos
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(15, 8, 'Cant.', 0, 0);
        $pdf->Cell(45, 8, 'Producto', 0, 0);
        $pdf->Cell(20, 8, 'P. Unit.', 0, 0, 'R');
        $pdf->Cell(20, 8, 'Subtotal', 0, 1, 'R');
        
        // Productos
        $pdf->SetFont('Arial', '', 10);
        foreach ($_SESSION['carrito'] as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            
            // Dividir nombre largo en múltiples líneas
            $nombre = wordwrap($item['nombre'], 25, "\n");
            $altura = max(6, (substr_count($nombre, "\n") + 1) * 6);
            
            $pdf->Cell(15, $altura, $item['cantidad'], 0, 0);
            $pdf->MultiCell(45, 6, $nombre, 0, 'L');
            $pdf->SetXY(68, $pdf->GetY() - $altura + 6);
            $pdf->Cell(20, $altura, '$' . number_format($item['precio'], 2), 0, 0, 'R');
            $pdf->Cell(20, $altura, '$' . number_format($subtotal, 2), 0, 1, 'R');
        }
        
        // Línea separadora doble
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, $pdf->GetY(), 140, $pdf->GetY());
        $pdf->Ln(5);
        $pdf->Line(10, $pdf->GetY(), 140, $pdf->GetY());
        $pdf->Ln(8);
        
        // Total
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(65, 10, 'TOTAL:', 0, 0, 'R');
        $pdf->Cell(25, 10, '$' . number_format($total, 2), 0, 1, 'R');
        
        // Pie de página
        $pdf->Ln(12);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(0, 6, 'Gracias por su compra!', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Por favor paga en caja', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Este comprobante no sirve si no esta sellado', 0, 1, 'C');
        
        $filename = $ticketsDir . '/ticket_' . $numero_ticket . '.pdf';
        
        // 6. Generar PDF
        $pdf->Output('F', $filename);
        
        // 7. Verificar que se creó
        if (!file_exists($filename)) {
            throw new Exception('Error al crear el archivo PDF');
        }
        
        // 8. Vaciar carrito solo si todo salió bien
        $_SESSION['carrito'] = [];
        $_SESSION['mensaje'] = "Pedido completado y ticket generado (Pedido ID: $pedido_id)";
        
        // 9. Forzar descarga
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="ticket_' . $numero_ticket . '.pdf"');
        readfile($filename);
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        $_SESSION['error'] = "Error al procesar el pedido: " . $e->getMessage();
        header('Location: vendedor.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de vendedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background: url('https://plus.unsplash.com/premium_photo-1661883237884-263e8de8869b?q=80&w=2089&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            min-height: 100vh;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }
        
        header {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #4CAF50;
            box-shadow: 0 0 20px rgba(76, 175, 80, 0.6);
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            padding-bottom: 100px; /* Espacio para el carrito flotante */
        }
        
        .productos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .producto {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .producto:hover {
            transform: translateY(-5px);
            border-color: #4CAF50;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.5);
        }
        
        .producto img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 2px solid #4CAF50;
        }
        
        .producto-info {
            padding: 20px;
        }
        
        .producto h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.2em;
        }
        
        .producto p {
            color: #555;
            margin: 8px 0;
            font-size: 0.9em;
        }
        
        .precio {
            font-weight: bold;
            color: #e63946;
            font-size: 1.3em;
            margin: 10px 0;
        }
        
        .mensaje {
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            animation: fadeInOut 3s ease-in-out;
        }
        
        @keyframes fadeInOut {
            0% { opacity: 0; top: 0; }
            10% { opacity: 1; top: 20px; }
            90% { opacity: 1; top: 20px; }
            100% { opacity: 0; top: 0; }
        }
        
        .form-group {
            margin-top: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-success:hover, .btn-danger:hover, .btn-primary:hover, .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        
        /* Carrito flotante */
        .floating-cart {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
            width: 350px;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 999;
            transition: all 0.3s;
            transform: translateY(100px);
            opacity: 0;
            visibility: hidden;
            border: 2px solid #4CAF50;
        }
        
        .floating-cart.visible {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
        }
        
        .cart-header {
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .cart-body {
            padding: 15px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        
        .cart-item-info {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        
        .cart-item-details {
            flex-grow: 1;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
        }
        
        .cart-item-quantity input {
            width: 50px;
            text-align: center;
            margin: 0 5px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .cart-total {
            font-size: 1.3em;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            color: #e63946;
        }
        
        .cart-actions {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        
        .cart-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 998;
            transition: all 0.3s;
        }
        
        .cart-toggle:hover {
            transform: scale(1.1);
        }
        
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e63946;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .productos {
                grid-template-columns: 1fr;
            }
            
            .floating-cart {
                width: 90%;
                right: 5%;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-store-alt"></i> Panel de vendedor</h1>
        <div>
            <span style="color: #4CAF50; font-weight: bold;">
                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
            <a href="logout.php" style="color: white; margin-left: 15px; font-weight: bold;">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
            </div>
        <?php endif; ?>

        <!-- Mostrar productos por categoría -->
        <?php foreach ($productosPorCategoria as $nombreCategoria => $productos): ?>
            <h2 style="color: white; text-shadow: 0 0 5px #4CAF50; margin: 30px 0 20px 0;">
                <i class="fas <?php echo $nombreCategoria == 'Comidas' ? 'fa-utensils' : 'fa-glass-whiskey'; ?>"></i> 
                <?php echo htmlspecialchars($nombreCategoria); ?>
            </h2>
            
            <div class="productos">
                <?php foreach ($productos as $producto): ?>
                    <div class="producto">
                        <img src="images/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <div class="producto-info">
                            <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            <p class="precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                            <form method="post">
                                <div class="form-group">
                                    <label for="cantidad_<?php echo $producto['id']; ?>">Cantidad:</label>
                                    <input type="number" id="cantidad_<?php echo $producto['id']; ?>" name="cantidad" 
                                           value="1" min="1" class="form-control">
                                </div>
                                <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                <button type="submit" name="agregar_carrito" class="btn btn-success">
                                    <i class="fas fa-cart-plus"></i> Agregar al carrito
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Carrito flotante -->
    <div class="floating-cart" id="floatingCart">
        <div class="cart-header" onclick="toggleCart()">
            <h3><i class="fas fa-shopping-cart"></i> Carrito de compras</h3>
            <i class="fas fa-chevron-down" id="cartToggleIcon"></i>
        </div>
        <div class="cart-body">
            <?php if (empty($_SESSION['carrito'])): ?>
                <p>El carrito está vacío</p>
            <?php else: ?>
                <?php $total = 0; ?>
                <?php foreach ($_SESSION['carrito'] as $index => $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <img src="images/<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                            <div class="cart-item-details">
                                <h4><?php echo htmlspecialchars($item['nombre']); ?></h4>
                                <p>$<?php echo number_format($item['precio'], 2); ?></p>
                            </div>
                        </div>
                        <div class="cart-item-quantity">
                            <form method="post" style="display: flex; align-items: center;">
                                <input type="hidden" name="item_index" value="<?php echo $index; ?>">
                                <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" min="1" 
                                       onchange="this.form.submit()" style="width: 50px;">
                                <button type="submit" name="actualizar_cantidad" style="display: none;"></button>
                            </form>
                            <a href="?eliminar=<?php echo $index; ?>" class="btn btn-danger" style="margin-left: 10px;">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                    <?php $total += $item['precio'] * $item['cantidad']; ?>
                <?php endforeach; ?>
                
                <div class="cart-total">
                    Total: $<?php echo number_format($total, 2); ?>
                </div>
                
                <div class="cart-actions">
                    <a href="?vaciar=1" class="btn btn-warning">
                        <i class="fas fa-trash"></i> Vaciar
                    </a>
                    <form method="post">
                        <button type="submit" name="generar_ticket" class="btn btn-primary">
                            <i class="fas fa-file-pdf"></i>Finalizar compra
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botón para mostrar/ocultar carrito -->
    <div class="cart-toggle" onclick="toggleCart()">
        <i class="fas fa-shopping-cart"></i>
        <?php if (!empty($_SESSION['carrito'])): ?>
            <span class="cart-badge"><?php echo count($_SESSION['carrito']); ?></span>
        <?php endif; ?>
    </div>

    <script>
        // Mostrar/ocultar carrito
        function toggleCart() {
            const cart = document.getElementById('floatingCart');
            const icon = document.getElementById('cartToggleIcon');
            
            cart.classList.toggle('visible');
            
            if (cart.classList.contains('visible')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
        
        // Mostrar carrito automáticamente si hay productos
        document.addEventListener('DOMContentLoaded', function() {
            const cart = document.getElementById('floatingCart');
            const icon = document.getElementById('cartToggleIcon');
            
            <?php if (!empty($_SESSION['carrito'])): ?>
                cart.classList.add('visible');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            <?php endif; ?>
            
            // Ocultar mensaje después de 3 segundos
            setTimeout(() => {
                const mensaje = document.querySelector('.mensaje');
                if (mensaje) {
                    mensaje.style.display = 'none';
                }
            }, 3000);
        });
    </script>
</body>
</html>