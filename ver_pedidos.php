<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'admin' && $_SESSION['rol'] !== 'vendedor')) {
    header('Location: login.php');
    exit();
}

// Obtener todos los pedidos con información del usuario
$stmt = $pdo->query("
    SELECT p.*, u.nombre as cliente 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id
    ORDER BY p.fecha DESC
");
$pedidos = $stmt->fetchAll();

// Obtener detalles de un pedido específico si se solicita
$detalles = [];
if (isset($_GET['ver_detalles']) && is_numeric($_GET['ver_detalles'])) {
    $pedido_id = $_GET['ver_detalles'];
    
    $stmt = $pdo->prepare("
        SELECT dp.*, pr.nombre as producto_nombre 
        FROM detalles_pedido dp
        JOIN productos pr ON dp.producto_id = pr.id
        WHERE dp.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $detalles = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Pedidos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos similares a vendedor.php */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        .total {
            font-weight: bold;
            color: #e63946;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-history"></i> Historial de Pedidos</h1>
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje">
                <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
            </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td><?= $pedido['id'] ?></td>
                    <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
                    <td><?= ucfirst($pedido['estado']) ?></td>
                    <td>
                        <a href="?ver_detalles=<?= $pedido['id'] ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (!empty($detalles)): ?>
        <h2>Detalles del Pedido #<?= $_GET['ver_detalles'] ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_pedido = 0;
                foreach ($detalles as $detalle): 
                    $subtotal = $detalle['precio_unitario'] * $detalle['cantidad'];
                    $total_pedido += $subtotal;
                ?>
                <tr>
                    <td><?= htmlspecialchars($detalle['producto_nombre']) ?></td>
                    <td><?= $detalle['cantidad'] ?></td>
                    <td>$<?= number_format($detalle['precio_unitario'], 2) ?></td>
                    <td>$<?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="total">TOTAL</td>
                    <td class="total">$<?= number_format($total_pedido, 2) ?></td>
                </tr>
            </tbody>
        </table>
        <a href="ver_pedidos.php" class="btn btn-info">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
        <?php endif; ?>
    </div>
</body>
</html>