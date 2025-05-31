<?php
session_start();
require 'db.php';

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtener el usuario actual con todos los datos desde la base de datos
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

if (!$current_user || $current_user['rol'] !== 'admin') {
    $_SESSION['error'] = "No tienes permisos para acceder a esta sección";
    header('Location: index.php');
    exit();
}

// Obtener datos para el panel
$productos = $pdo->query("SELECT * FROM productos")->fetchAll();
$usuarios = $pdo->query("SELECT * FROM usuarios WHERE activo = 1")->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Token de seguridad inválido";
        header('Location: admin.php');
        exit();
    }

    if (isset($_POST['agregar_producto'])) {
        // Validar permisos
        if ($current_user['rol'] !== 'admin') {
            $_SESSION['error'] = "No tienes permisos para esta acción";
            header('Location: admin.php');
            exit();
        }
        
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $precio = $_POST['precio'];
        $stock = $_POST['stock'];
        $imagen = 'default.jpg';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], 'images/' . $imagen);
        }
        $categoria_id = $_POST['categoria_id'];
$stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, imagen, stock, categoria_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$nombre, $descripcion, $precio, $imagen, $stock, $categoria_id]);

        $_SESSION['mensaje'] = "Producto agregado correctamente";
        header('Location: admin.php');
        exit();
        
} elseif (isset($_POST['editar_producto'])) {
    // Validar permisos
    if ($current_user['rol'] !== 'admin') {
        $_SESSION['error'] = "No tienes permisos para esta acción";
        header('Location: admin.php');
        exit();
    }
    
    // Obtener datos del formulario
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $categoria_id = $_POST['categoria_id'];
    
    // Validaciones básicas
    if (empty($nombre) || empty($descripcion) || !is_numeric($precio) || !is_numeric($stock) || !is_numeric($categoria_id)) {
        $_SESSION['error'] = "Datos del producto inválidos";
        header('Location: admin.php');
        exit();
    }

    try {
        // Manejo de la imagen (si se subió una nueva)
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imagen = basename($_FILES['imagen']['name']);
            $extension = strtolower(pathinfo($imagen, PATHINFO_EXTENSION));
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Validar extensión del archivo
            if (!in_array($extension, $extensionesPermitidas)) {
                $_SESSION['error'] = "Formato de imagen no permitido. Use JPG, PNG o GIF";
                header('Location: admin.php');
                exit();
            }
            
            // Mover archivo y actualizar con nueva imagen
            $nombreUnico = uniqid() . '.' . $extension;
            move_uploaded_file($_FILES['imagen']['tmp_name'], 'images/' . $nombreUnico);
            
            $stmt = $pdo->prepare("UPDATE productos SET 
                                nombre = ?, 
                                descripcion = ?, 
                                precio = ?, 
                                imagen = ?, 
                                stock = ?, 
                                categoria_id = ? 
                                WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $precio, $nombreUnico, $stock, $categoria_id, $id]);
        } else {
            // Actualizar sin cambiar la imagen
            $stmt = $pdo->prepare("UPDATE productos SET 
                                nombre = ?, 
                                descripcion = ?, 
                                precio = ?, 
                                stock = ?, 
                                categoria_id = ? 
                                WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $precio, $stock, $categoria_id, $id]);
        }
        
        $_SESSION['mensaje'] = "Producto actualizado correctamente";
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar el producto: " . $e->getMessage();
    }
    
    header('Location: admin.php');
    exit();
}
        
} elseif (isset($_POST['eliminar_producto'])) {
    // Validar permisos
    if ($current_user['rol'] !== 'admin') {
        $_SESSION['error'] = "No tienes permisos para esta acción";
        header('Location: admin.php');
        exit();
    }
    
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Token de seguridad inválido";
        header('Location: admin.php');
        exit();
    }
    
    $id = $_POST['id'];
    
    try {
        // Iniciar transacción para integridad de datos
        $pdo->beginTransaction();
        
        // 1. Eliminar detalles de pedido relacionados
        $stmt = $pdo->prepare("DELETE FROM detalles_pedido WHERE producto_id = ?");
        $stmt->execute([$id]);
        
        // 2. Eliminar el producto
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        
        // Confirmar transacción
        $pdo->commit();
        
        $_SESSION['mensaje'] = "Producto eliminado correctamente";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error al eliminar producto: " . $e->getMessage();
    }
    
    header('Location: admin.php');
    exit();
}
        
    elseif (isset($_POST['actualizar_stock'])) {
        // Validar permisos (permite admin y vendedor)
        if (!in_array($current_user['rol'], ['admin', 'vendedor'])) {
            $_SESSION['error'] = "No tienes permisos para esta acción";
            header('Location: admin.php');
            exit();
        }
        
        $id = $_POST['id'];
        $nuevo_stock = $_POST['stock'];
        
        $stmt = $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $stmt->execute([$nuevo_stock, $id]);

        $_SESSION['mensaje'] = "Stock actualizado correctamente";
        header('Location: admin.php');
        exit();
        
    } elseif (isset($_POST['agregar_usuario'])) {
        // Solo admin puede agregar usuarios
        if ($current_user['rol'] !== 'admin') {
            $_SESSION['error'] = "No tienes permisos para esta acción";
            header('Location: admin.php');
            exit();
        }
        
        $username = trim($_POST['username']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $rol = $_POST['rol'];
        $nombre = trim($_POST['nombre']);

        // Validar datos
        if (empty($username) || empty($nombre) || empty($_POST['password'])) {
            $_SESSION['error'] = "Todos los campos son requeridos";
            header('Location: admin.php#seccion-usuarios');
            exit();
        }

        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "El nombre de usuario ya existe";
            header('Location: admin.php#seccion-usuarios');
            exit();
        }

        // Insertar nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol, nombre) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $password, $rol, $nombre])) {
            $_SESSION['mensaje'] = "Usuario agregado correctamente";
            $usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll();
        } else {
            $_SESSION['error'] = "Error al agregar usuario";
        }

        header('Location: admin.php#seccion-usuarios');
        exit();
        
    } elseif (isset($_POST['eliminar_usuario'])) {
    // Verificar permisos
    if ($current_user['rol'] !== 'admin') {
        $_SESSION['error'] = "No tienes permisos para esta acción";
        header('Location: admin.php');
        exit();
    }
    
    $id = $_POST['id'];
    
    // No permitir eliminarse a sí mismo
    if ($id == $current_user['id']) {
        $_SESSION['error'] = "No puedes eliminarte a ti mismo";
        header('Location: admin.php#seccion-usuarios');
        exit();
    }

    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // 1. Primero eliminar los detalles de pedido asociados a los pedidos del usuario
        $stmt = $pdo->prepare("DELETE dp FROM detalles_pedido dp
                              INNER JOIN pedidos p ON dp.pedido_id = p.id
                              WHERE p.usuario_id = ?");
        $stmt->execute([$id]);
        
        // 2. Luego eliminar los pedidos del usuario
        $stmt = $pdo->prepare("DELETE FROM pedidos WHERE usuario_id = ?");
        $stmt->execute([$id]);
        
        // 3. Finalmente eliminar el usuario
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        
        // Confirmar la transacción
        $pdo->commit();
        
        $_SESSION['mensaje'] = "Vendedor y todos sus registros relacionados eliminados correctamente";
        $usuarios = $pdo->query("SELECT * FROM usuarios")->fetchAll();
    } catch (PDOException $e) {
        // Revertir la transacción en caso de error
        $pdo->rollBack();
        $_SESSION['error'] = "Error al eliminar vendedor: " . $e->getMessage();
    }

    header('Location: admin.php#seccion-usuarios');
    exit();
}
        
    elseif (isset($_POST['agregar_categoria'])) {
        // Solo admin puede agregar categorías
        if ($current_user['rol'] !== 'admin') {
            $_SESSION['error'] = "No tienes permisos para esta acción";
            header('Location: admin.php');
            exit();
        }
        
        $nombre = trim($_POST['nombre']);
        $icono = trim($_POST['icono']);
        
        if (empty($nombre) || empty($icono)) {
            $_SESSION['error'] = "Todos los campos son requeridos";
            header('Location: admin.php#seccion-categorias');
            exit();
        }
        
        $stmt = $pdo->prepare("INSERT INTO categorias (nombre, icono) VALUES (?, ?)");
        if ($stmt->execute([$nombre, $icono])) {
            $_SESSION['mensaje'] = "Categoría agregada correctamente";
            $categorias = $pdo->query("SELECT * FROM categorias")->fetchAll();
        } else {
            $_SESSION['error'] = "Error al agregar categoría";
        }
        
        header('Location: admin.php#seccion-categorias');
        exit();
        
    } elseif (isset($_POST['eliminar_categoria'])) {
        // Solo admin puede eliminar categorías
        if ($current_user['rol'] !== 'admin') {
            $_SESSION['error'] = "No tienes permisos para esta acción";
            header('Location: admin.php');
            exit();
        }
        
        $id = $_POST['id'];
        
        // Verificar si hay productos asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "No se puede eliminar la categoría porque tiene productos asociados";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            if ($stmt->execute([$id])) {
                $_SESSION['mensaje'] = "Categoría eliminada correctamente";
                $categorias = $pdo->query("SELECT * FROM categorias")->fetchAll();
            } else {
                $_SESSION['error'] = "Error al eliminar categoría";
            }
        }
        
        header('Location: admin.php#seccion-categorias');
        exit();
    }


// Generar token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de administración</title>
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
            display: flex;
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
        
        .sidebar {
            width: 250px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            min-height: 100vh;
            transition: all 0.3s;
            position: fixed;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.5);
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.9);
            border-bottom: 2px solid #2196F3;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu h3 {
            color: #2196F3;
            font-size: 16px;
            margin: 15px 20px 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid #2196F3;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-toggle {
            position: fixed;
            left: 10px;
            top: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 101;
            font-size: 20px;
            transition: all 0.3s;
        }
        
        .sidebar-toggle:hover {
            background: #2196F3;
        }
        
        .sidebar-collapsed {
            margin-left: -250px;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            transition: all 0.3s;
        }
        
        .main-content-expanded {
            margin-left: 0;
        }
        
        header {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2196F3;
            box-shadow: 0 0 20px rgba(33, 150, 243, 0.6);
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .productos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
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
            border-color: #2196F3;
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.5);
        }
        
        .producto img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 2px solid #2196F3;
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
        
        .stock-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .stock-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        
        .stock-btn {
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .stock-btn:hover {
            background: #45a049;
        }
        
        .mensaje {
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        .error {
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        
        .acciones {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-primary {
            background: #2196F3;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0b7dda;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(33, 150, 243, 0.3);
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-danger:hover {
            background: #da190b;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(244, 67, 54, 0.3);
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-success:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(76, 175, 80, 0.3);
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(255, 193, 7, 0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            border: 2px solid #2196F3;
        }
        
        .modal h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
            font-size: 16px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .tabla {
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .tabla th, .tabla td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .tabla th {
            background-color: #2196F3;
            color: white;
            font-weight: bold;
        }
        
        .tabla tr:nth-child(even) {
            background-color: rgba(33, 150, 243, 0.05);
        }
        
        .tabla tr:hover {
            background-color: rgba(33, 150, 243, 0.1);
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-admin {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-vendedor {
            background-color: #fd7e14;
            color: white;
        }
        
        .badge-cliente {
            background-color: #28a745;
            color: white;
        }
        
        .icono-categoria {
            margin-right: 8px;
            color: #2196F3;
        }
        
        @media (max-width: 768px) {
            .productos {
                grid-template-columns: 1fr;
            }
            
            .acciones {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Botón para mostrar/ocultar el panel lateral -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Panel lateral -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-crown"></i> Panel</h2>
        </div>
        
        <div class="sidebar-menu">
            <h3>Principal</h3>
            <a href="#" onclick="mostrarSeccion('productos')"><i class="fas fa-box-open"></i> Productos</a>
            <a href="#" onclick="mostrarSeccion('usuarios')"><i class="fas fa-users"></i> Usuarios</a>
            <a href="#" onclick="mostrarSeccion('categorias')"><i class="fas fa-tags"></i> Categorías</a>
            
            <h3>Acciones</h3>
            <a href="#" onclick="document.getElementById('modal-agregar').style.display='flex'"><i class="fas fa-plus-circle"></i> Agregar producto</a>
            <a href="#" onclick="document.getElementById('modal-agregar-usuario').style.display='flex'"><i class="fas fa-user-plus"></i> Agregar usuario</a>
            <a href="#" onclick="document.getElementById('modal-agregar-categoria').style.display='flex'"><i class="fas fa-tag"></i> Agregar categoría</a>
            
            <h3>Sesión</h3>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="main-content" id="main-content">
        <header>
            <h1><i class="fas fa-crown"></i> Panel de administración</h1>
            <div>
                <span style="color: #2196F3; font-weight: bold;">
                    <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($current_user['nombre']); ?>
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
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Sección Productos -->
            <div id="seccion-productos">
                <button onclick="document.getElementById('modal-agregar').style.display='flex'" class="btn btn-success">
                    <i class="fas fa-plus"></i> Agregar producto
                </button>

                <h2 style="color: white; text-shadow: 0 0 5px #2196F3; margin: 20px 0;">
                    <i class="fas fa-box-open"></i> Gestión de productos
                </h2>
                <div class="productos">
                    <?php foreach ($productos as $producto): ?>
                        <div class="producto">
                            <img src="images/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            <div class="producto-info">
                                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                <p class="precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                                
                                <form method="post" class="stock-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                                    <span>Stock:</span>
                                    <input type="number" name="stock" value="<?php echo $producto['stock']; ?>" class="stock-input" min="0">
                                    <button type="submit" name="actualizar_stock" class="stock-btn">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                                
                                <div class="acciones">
                                   <button class="btn btn-primary" onclick="editarProducto(
    <?php echo $producto['id']; ?>, 
    '<?php echo htmlspecialchars($producto['nombre']); ?>', 
    '<?php echo htmlspecialchars($producto['descripcion']); ?>', 
    <?php echo $producto['precio']; ?>, 
    <?php echo $producto['stock']; ?>,
    <?php echo $producto['categoria_id']; ?> 
)">
    <i class="fas fa-edit"></i> Editar
</button>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                                        <button type="submit" name="eliminar_producto" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sección Usuarios -->
            <div id="seccion-usuarios" style="display: none;">
                <button onclick="document.getElementById('modal-agregar-usuario').style.display='flex'" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Agregar usuario
                </button>

                <h2 style="color: white; text-shadow: 0 0 5px #2196F3; margin: 20px 0;">
                    <i class="fas fa-users"></i> Gestión de usuarios
                </h2>
                
                <div class="tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <?php echo ($usuario['activo']) ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'; ?>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                                    <td>
                                        <?php if ($usuario['rol'] === 'admin'): ?>
                                            <span class="badge badge-admin">Admin</span>
                                        <?php elseif ($usuario['rol'] === 'vendedor'): ?>
                                            <span class="badge badge-vendedor">Vendedor</span>
                                        <?php else: ?>
                                            <span class="badge badge-cliente">Cliente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" name="eliminar_usuario" class="btn btn-danger btn-sm" <?php echo ($usuario['id'] == $current_user['id']) ? 'disabled' : ''; ?>>
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sección Categorías -->
            <div id="seccion-categorias" style="display: none;">
                <button onclick="document.getElementById('modal-agregar-categoria').style.display='flex'" class="btn btn-success">
                    <i class="fas fa-tag"></i> Agregar categoría
                </button>

                <h2 style="color: white; text-shadow: 0 0 5px #2196F3; margin: 20px 0;">
                    <i class="fas fa-tags"></i> Gestión de categorías
                </h2>
                
                <div class="tabla">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $categoria): ?>
                                <tr>
                                    <td><?php echo $categoria['id']; ?></td>
                                    <td>
                                        <i class="fas <?php echo ($categoria['nombre'] === 'Comidas') ? 'fa-utensils' : 'fa-glass-whiskey'; ?> icono-categoria"></i>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="id" value="<?php echo $categoria['id']; ?>">
                                            <button type="submit" name="eliminar_categoria" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Producto -->
    <div id="modal-agregar" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-plus-circle"></i> Agregar Producto</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="precio">Precio:</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
    <label for="editar-categoria">Categoría:</label>
    <select id="editar-categoria" name="categoria_id" class="form-control" required>
        <?php foreach ($categorias as $categoria): ?>
            <option value="<?php echo $categoria['id']; ?>">
                <?php echo htmlspecialchars($categoria['nombre']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                <div class="form-group">
                    <label for="stock">Stock:</label>
                    <input type="number" id="stock" name="stock" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="imagen">Imagen:</label>
                    <input type="file" id="imagen" name="imagen" class="form-control" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="document.getElementById('modal-agregar').style.display='none'" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="agregar_producto" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div id="modal-editar" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-edit"></i> Editar Producto</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" id="editar-id" name="id">
                <div class="form-group">
                    <label for="editar-nombre">Nombre:</label>
                    <input type="text" id="editar-nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editar-descripcion">Descripción:</label>
                    <textarea id="editar-descripcion" name="descripcion" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editar-precio">Precio:</label>
                    <input type="number" id="editar-precio" name="precio" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="form-group">
    <label for="editar-categoria">Categoría:</label>
    <select id="editar-categoria" name="categoria_id" class="form-control" required>
        <?php foreach ($categorias as $categoria): ?>
            <option value="<?php echo $categoria['id']; ?>">
                <?php echo htmlspecialchars($categoria['nombre']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                <div class="form-group">
                    <label for="editar-stock">Stock:</label>
                    <input type="number" id="editar-stock" name="stock" min="0" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editar-imagen">Nueva Imagen (opcional):</label>
                    <input type="file" id="editar-imagen" name="imagen" class="form-control" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="document.getElementById('modal-editar').style.display='none'" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="editar_producto" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Agregar Usuario -->
    <div id="modal-agregar-usuario" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-user-plus"></i> Agregar Usuario</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="username">Nombre de usuario:</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre completo:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="rol">Rol:</label>
                    <select id="rol" name="rol" class="form-control" required>
                        <option value="admin">Administrador</option>
                        <option value="vendedor">Vendedor</option>
                        <option value="cliente">Cliente</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="document.getElementById('modal-agregar-usuario').style.display='none'" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="agregar_usuario" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Agregar Categoría -->
    <div id="modal-agregar-categoria" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-tag"></i> Agregar Categoría</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="nombre-categoria">Nombre:</label>
                    <input type="text" id="nombre-categoria" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="icono-categoria">Icono (Font Awesome):</label>
                    <input type="text" id="icono-categoria" name="icono" class="form-control" placeholder="Ej: fa-utensils" required>
                    <small>Usar nombres de iconos de Font Awesome (ej: fa-utensils, fa-glass-whiskey)</small>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="document.getElementById('modal-agregar-categoria').style.display='none'" class="btn btn-danger">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" name="agregar_categoria" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
// Función para confirmar eliminación
function confirmarEliminacion(form) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
    return false;
}

        // Función para mostrar/ocultar el panel lateral
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            sidebar.classList.toggle('sidebar-collapsed');
            mainContent.classList.toggle('main-content-expanded');
        }

        // Función para mostrar una sección específica
        function mostrarSeccion(seccion) {
            // Ocultar todas las secciones
            document.getElementById('seccion-productos').style.display = 'none';
            document.getElementById('seccion-usuarios').style.display = 'none';
            document.getElementById('seccion-categorias').style.display = 'none';
            
            // Mostrar la sección seleccionada
            document.getElementById('seccion-' + seccion).style.display = 'block';
        }

        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        // Función para editar producto
function editarProducto(id, nombre, descripcion, precio, stock, categoria_id) {
    document.getElementById('editar-id').value = id;
    document.getElementById('editar-nombre').value = nombre;
    document.getElementById('editar-descripcion').value = descripcion;
    document.getElementById('editar-precio').value = precio;
    document.getElementById('editar-stock').value = stock;
    document.getElementById('editar-categoria').value = categoria_id; 
    document.getElementById('modal-editar').style.display = 'flex';
}
    </script>
</body>
</html>