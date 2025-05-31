<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['nombre'] = $user['nombre'];

        switch ($user['rol']) {
            case 'admin':
                header('Location: admin.php');
                break;
            case 'vendedor':
                header('Location: vendedor.php');
                break;
            case 'cliente':
                header('Location: cliente.php');
                break;
            default:
                header('Location: index.php');
        }
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - D&P</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos generales */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background: url('https://images.unsplash.com/photo-1552566626-2d907dab0dff?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            filter: blur(8px) brightness(0.7);
            z-index: -1;
        }

        .login-container {
            width: 90%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.85); 
            border-radius: 25px;
            padding: 30px;
            box-shadow: 
                0 0 10px rgba(0, 255, 0, 0.5),
                0 0 20px rgba(218, 165, 32, 0.5),
                0 0 30px rgba(0, 255, 0, 0.3),
                0 10px 25px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00ff00, #daa520, #00ff00);
            z-index: -1;
            border-radius: 25px;
            background-size: 200% 200%;
            animation: waveGradient 4s ease infinite, animateBorder 6s linear infinite;
            opacity: 0.7;
        }
        
        @keyframes waveGradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        @keyframes animateBorder {
            0% {
                filter: blur(5px);
                opacity: 0.7;
            }
            50% {
                filter: blur(8px);
                opacity: 0.5;
            }
            100% {
                filter: blur(5px);
                opacity: 0.7;
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgb(113, 123, 0);
            animation: borderPulse 3s infinite alternate;
        }
        
        @keyframes borderPulse {
            0% {
                border-color: rgb(113, 123, 0);
                box-shadow: 0 0 10px rgba(113, 123, 0, 0.5);
            }
            50% {
                border-color: #daa520;
                box-shadow: 0 0 15px rgba(218, 165, 32, 0.7);
            }
            100% {
                border-color: rgb(113, 123, 0);
                box-shadow: 0 0 10px rgba(113, 123, 0, 0.5);
            }
        }
        
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
            font-size: 28px;
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        h2::after {
            content: '';
            display: block;
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, #00ff00, #daa520);
            margin: 10px auto 0;
            border-radius: 3px;
            animation: waveWidth 3s ease-in-out infinite alternate;
        }
        
        @keyframes waveWidth {
            0% {
                width: 60%;
                background-position: left;
            }
            100% {
                width: 80%;
                background-position: right;
            }
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 40px;
            animation: iconColorWave 4s infinite ease-in-out;
        }
        
        @keyframes iconColorWave {
            0%, 100% {
                color: rgb(59, 126, 0);
            }
            50% {
                color: #daa520;
            }
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid #ddd;
            border-radius: 30px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #00a859;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 168, 89, 0.5);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #00a859, #daa520);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            background-size: 200% 200%;
            animation: gradientWave 3s ease infinite;
        }
        
        @keyframes gradientWave {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 168, 89, 0.4);
        }
        
        .error {
            color: #ff3333;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .support {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }
        
        .whatsapp-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #25D366, #128C7E);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
            background-size: 200% 200%;
            animation: whatsappWave 4s ease infinite;
        }
        
        @keyframes whatsappWave {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }
        
        .whatsapp-btn i {
            margin-right: 8px;
            font-size: 18px;
        }
        
        .whatsapp-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.4);
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #777;
            font-size: 14px;
        }
        
        .footer-text p:first-child {
            animation: textColorWave 6s infinite ease-in-out;
        }
        
        @keyframes textColorWave {
            0%, 100% {
                color: #555;
            }
            50% {
                color: #daa520;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="https://media3.giphy.com/media/v1.Y2lkPTc5MGI3NjExZnh4cXRjemFlNHphZ3p2NDNmaWQ2cGh5anoxeTllcHZmNzg2eGw1OSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/QZsb5vu2awvAD2DUEv/giphy.gif" alt="Logo Comida">
        </div>
        
        <h2>Iniciar sesión</h2>
        
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">
                    Usuario:
                </label>
                <i class="fas fa-user input-icon"></i>
                <input type="text" id="username" name="username" placeholder="Ingresa tu usuario" required>
            </div>
            
            <div class="form-group">
                <label for="password">
                    Contraseña:
                </label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
            </div>
            
            <button type="submit">Ingresar</button>
        </form>
        
        <div class="support">
            <p>¿Problemas con tu usuario o contraseña?</p>
            <a href="https://wa.me/5621181453?text=Hola,%20tengo%20problemas%20con%20mi%20usuario%20o%20contraseña" class="whatsapp-btn" target="_blank">
                <i class="fab fa-whatsapp"></i> Contáctanos
            </a>
        </div>
        
        <div class="footer-text">
            <p>D&P S.A de C.V © 2025</p>
            <p>Come frutas y verduras</p>
        </div>
    </div>
</body>
</html>