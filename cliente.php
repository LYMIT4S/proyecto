<?php
session_start();
require 'db.php';

// Verificar si el usuario está logueado y es cliente
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    header('Location: login.php');
    exit();
}

// Obtener el nombre del usuario para mostrar en el header
$nombre_usuario = $_SESSION['nombre'] ?? 'Cliente';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - Cliente</title>
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
            border-bottom: 2px solid #ff69b4;
            box-shadow: 0 0 20px rgba(255, 105, 180, 0.6);
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .categoria {
            margin-bottom: 30px;
        }
        
        .categoria h2 {
            color: white;
            text-shadow: 0 0 5px #ff69b4;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #ff69b4;
        }
        
        .productos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .producto {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            border: 2px solid transparent;
            position: relative;
            perspective: 1000px;
            height: 300px;
        }
        
        .producto:hover {
            transform: translateY(-5px);
            border-color: #ff69b4;
            box-shadow: 0 8px 25px rgba(255, 105, 180, 0.5);
        }
        
        .producto-contenedor {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }
        
        .producto:hover .producto-contenedor {
            transform: rotateY(180deg);
        }
        
        .producto-front, .producto-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
        }
        
        .producto-front {
            display: flex;
            flex-direction: column;
        }
        
        .producto-back {
            background: white;
            transform: rotateY(180deg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 15px;
            color: #333;
        }
        
        .producto img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 2px solid #ff69b4;
        }
        
        .producto-info {
            padding: 15px;
            flex-grow: 1;
        }
        
        .producto h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.1em;
        }
        
        .precio {
            font-weight: bold;
            color: #e63946;
            font-size: 1.2em;
            margin: 8px 0;
        }
        
        .whatsapp-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #25D366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
            z-index: 1000;
            text-decoration: none;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            transform: scale(1.1);
        }
        
        .whatsapp-tooltip {
            position: absolute;
            right: 70px;
            background: white;
            color: #333;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
            white-space: nowrap;
        }
        
        .whatsapp-btn:hover .whatsapp-tooltip {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .productos {
                grid-template-columns: 1fr 1fr;
            }
            
            .whatsapp-btn {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 25px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-utensils"></i> Menú del restaurante</h1>
        <div>
            <span style="color: #ff69b4; font-weight: bold;">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($nombre_usuario); ?>
            </span>
            <a href="logout.php" style="color: white; margin-left: 15px; font-weight: bold;">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </header>

    <div class="container">
        <?php
        // Consulta para obtener productos por categoría
        $stmt = $pdo->prepare("
            SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, c.nombre AS categoria 
            FROM productos p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE c.id = ?
            ORDER BY p.nombre
        ");
        
        // Mostrar categoría COMIDAS (ID 1)
        $stmt->execute([1]);
        $comidas = $stmt->fetchAll();
        
        if (count($comidas) > 0):
        ?>
        <div class="categoria">
            <h2><i class="fas fa-hamburger"></i> Comidas</h2>
            <div class="productos">
                <?php foreach ($comidas as $producto): ?>
                    <div class="producto">
                        <div class="producto-contenedor">
                            <div class="producto-front">
                                <img src="images/<?php echo htmlspecialchars($producto['imagen'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <div class="producto-info">
                                    <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                    <p class="precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                                </div>
                            </div>
                            <div class="producto-back">
                                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                <p class="precio" style="margin-top: auto;">$<?php echo number_format($producto['precio'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        // Mostrar categoría BEBIDAS (ID 2)
        $stmt->execute([2]);
        $bebidas = $stmt->fetchAll();
        
        if (count($bebidas) > 0):
        ?>
        <div class="categoria">
            <h2><i class="fas fa-glass-cheers"></i> Bebidas</h2>
            <div class="productos">
                <?php foreach ($bebidas as $producto): ?>
                    <div class="producto">
                        <div class="producto-contenedor">
                            <div class="producto-front">
                                <img src="images/<?php echo htmlspecialchars($producto['imagen'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <div class="producto-info">
                                    <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                    <p class="precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                                </div>
                            </div>
                            <div class="producto-back">
                                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                <p class="precio" style="margin-top: auto;">$<?php echo number_format($producto['precio'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Botón de WhatsApp flotante -->
    <a href="https://wa.me/5621181453?text=Hola,%20tengo%20una%20queja%20o%20sugerencia%20sobre%20el%20servicio" class="whatsapp-btn" target="_blank">
        <i class="fab fa-whatsapp"></i>
        <span class="whatsapp-tooltip">Quejas y sugerencias</span>
    </a>
</body>
</html>