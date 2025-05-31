<?php
$host = 'localhost';
$dbname = 'sistema_productos';
$username = 'root';
$password = 'root';

// Verificamos si se está accediendo directamente a este archivo
$directAccess = basename($_SERVER['SCRIPT_FILENAME']) == 'db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Solo mostramos la interfaz gráfica si es acceso directo
    if ($directAccess) {
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Conexión exitosa</title>
            <style>
                body {
                    background-color: #f8f9fa;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    font-family: 'Arial', sans-serif;
                    overflow: hidden;
                }
                
                .success-box {
                    text-align: center;
                    padding: 40px;
                    border-radius: 10px;
                    background: white;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                    position: relative;
                    max-width: 80%;
                    animation: rainbow-border 8s infinite linear;
                    border: 4px solid transparent;
                    background-clip: padding-box;
                }
                
                h1 {
                    margin-bottom: 20px;
                    font-size: 2.5em;
                    background: linear-gradient(90deg, #3498db, #9b59b6, #2ecc71);
                    -webkit-background-clip: text;
                    background-clip: text;
                    color: transparent;
                    animation: rainbow-text 8s infinite linear;
                }
                
                p {
                    color: #7f8c8d;
                    font-size: 1.2em;
                    margin: 15px 0;
                }
                
                .gif-container {
                    margin: 20px auto;
                    width: 200px;
                    height: 200px;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                    transition: transform 0.3s ease;
                }
                
                .gif-container:hover {
                    transform: scale(1.05);
                }
                
                .gif-container img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                
                @keyframes rainbow-border {
                    0% { border-color: #3498db; }
                    33% { border-color: #9b59b6; }
                    66% { border-color: #2ecc71; }
                    100% { border-color: #3498db; }
                }
                
                @keyframes rainbow-text {
                    0% { background-position: 0% 50%; }
                    100% { background-position: 100% 50%; }
                }
                
                .heart {
                    color: #e74c3c;
                    animation: heartbeat 1.5s infinite;
                    display: inline-block;
                }
                
                @keyframes heartbeat {
                    0% { transform: scale(1); }
                    25% { transform: scale(1.1); }
                    50% { transform: scale(1); }
                    75% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
            </style>
        </head>
        <body>
            <div class="success-box">
                <h1>¡Conexión exitosa!</h1>
                <p>Denle preciosos</p>
                
                <div class="gif-container">
                    <img src="https://i.gifer.com/U7Vv.gif" alt="Celebration GIF">
                </div>
                
                <p>Maestra ocupamos 10 <span class="heart">❤️</span></p>
            </div>
        </body>
        </html>
HTML;
    }

} catch (PDOException $e) {
    // Solo mostramos el error gráfico si es acceso directo
    if ($directAccess) {
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error de conexión</title>
            <style>
                body {
                    background-color: #f8f9fa;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    font-family: 'Arial', sans-serif;
                    overflow: hidden;
                }
                
                .error-box {
                    text-align: center;
                    padding: 40px;
                    border-radius: 10px;
                    background: white;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                    max-width: 80%;
                    border: 4px solid #e74c3c;
                }
                
                .error-title {
                    color: #e74c3c;
                    font-size: 2.5em;
                    margin-bottom: 20px;
                    text-transform: uppercase;
                }
                
                .error-message {
                    color: #7f8c8d;
                    font-size: 1.5em;
                    margin: 15px 0;
                    font-weight: bold;
                }
                
                .error-gif {
                    margin: 20px auto;
                    width: 200px;
                    height: 200px;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1 class="error-title">Error de conexión</h1>
                <p class="error-message">No le sabes chavo</p>
                <p class="error-message">Cámbiate a derecho</p>
                
                <div class="error-gif">
                    <img src="https://media.giphy.com/media/l3V0j3ytFyGHqiV7W/giphy.gif" alt="Fail GIF">
                </div>
                
                <p>Detalles técnicos: {$e->getMessage()}</p>
            </div>
        </body>
        </html>
HTML;
    } else {
        // Si no es acceso directo, solo mostramos el error técnico
        die("Error de conexión: " . $e->getMessage());
    }
}
?>