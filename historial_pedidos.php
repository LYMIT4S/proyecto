<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'vendedor') {
    header('Location: login.php');
    exit();
}

// Obtener todos los pedidos realizados por este vendedor
$stmt = $pdo->prepare("SELECT p.*, u.nombre as cliente_nombre 
                      FROM pedidos p
                      LEFT JOIN usuarios u ON p.usuario_id = u.id
                      WHERE p.vendedor_id = ?
                      ORDER BY p.fecha DESC");
$stmt->execute([$_SESSION['user_id']]);
$pedidos = $stmt->fetchAll();
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
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
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
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-history"></i> Historial de Pedidos</h1>
        <div>
            <span style="color: #4CAF50; font-weight: bold;">
                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
            </span>
            <a href="vendedor.php" style="color: white; margin-left: 15px; font-weight: bold;">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
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

        <h2><i class="fas fa-clipboard-list"></i> Pedidos Realizados</h2>
        
        <?php if (empty($pedidos)): ?>
            <p>No hay pedidos registrados.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pedido['numero_ticket']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($pedido['cliente_nombre'] ?? 'N/A'); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($pedido['estado'])); ?></td>
                            <td>
                                <a href="ver_pedido.php?id=<?php echo $pedido['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>