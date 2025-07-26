<?php
// Configuración básica
$archivo_contactos = 'contactos.txt';
$password_admin = 'Admin2018!'; // CAMBIAR EN PRODUCCIÓN

// Verificar autenticación simple
session_start();
$autenticado = isset($_SESSION['admin_autenticado']) && $_SESSION['admin_autenticado'] === true;

// Procesar login
if (isset($_POST['password'])) {
    if ($_POST['password'] === $password_admin) {
        $_SESSION['admin_autenticado'] = true;
        $autenticado = true;
    } else {
        $error_login = 'Contraseña incorrecta';
    }
}

// Procesar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ver-mensajes.php');
    exit;
}

// Función para leer mensajes
function leerMensajes($archivo) {
    $mensajes = [];
    
    if (file_exists($archivo)) {
        $contenido = file_get_contents($archivo);
        $mensajes_raw = explode("---SEPARADOR---", $contenido);
        
        foreach ($mensajes_raw as $mensaje_raw) {
            $mensaje_raw = trim($mensaje_raw);
            if (!empty($mensaje_raw)) {
                $mensaje = json_decode($mensaje_raw, true);
                if ($mensaje) {
                    $mensajes[] = $mensaje;
                }
            }
        }
    }
    
    // Ordenar por fecha (más recientes primero)
    usort($mensajes, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    return $mensajes;
}

// Procesar eliminación de mensaje
if ($autenticado && isset($_POST['eliminar_id'])) {
    $id_eliminar = $_POST['eliminar_id'];
    $mensajes = leerMensajes($archivo_contactos);
    $mensajes_filtrados = array_filter($mensajes, function($msg) use ($id_eliminar) {
        return $msg['id'] !== $id_eliminar;
    });
    
    // Reescribir archivo
    $contenido = '';
    foreach ($mensajes_filtrados as $mensaje) {
        $contenido .= json_encode($mensaje, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n---SEPARADOR---\n";
    }
    file_put_contents($archivo_contactos, $contenido);
    
    header('Location: ver-mensajes.php');
    exit;
}

// Leer mensajes si está autenticado
if ($autenticado) {
    $mensajes = leerMensajes($archivo_contactos);
    $total_mensajes = count($mensajes);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Mensajes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
        }
        
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: opacity 0.3s;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .login-form {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 400px;
            margin: 50px auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .login-form h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        
        .stats {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .mensajes-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .mensaje {
            border-bottom: 1px solid #eee;
            padding: 20px;
            transition: background-color 0.3s;
        }
        
        .mensaje:last-child {
            border-bottom: none;
        }
        
        .mensaje:hover {
            background-color: #f8f9fa;
        }
        
        .mensaje-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .mensaje-info {
            flex: 1;
        }
        
        .mensaje-nombre {
            font-weight: bold;
            color: #333;
            font-size: 18px;
        }
        
        .mensaje-email {
            color: #3498db;
            font-size: 14px;
        }
        
        .mensaje-fecha {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .mensaje-asunto {
            background: #ecf0f1;
            padding: 5px 10px;
            border-radius: 3px;
            display: inline-block;
            font-size: 14px;
            margin: 10px 0;
        }
        
        .mensaje-contenido {
            color: #555;
            line-height: 1.6;
            margin-top: 10px;
        }
        
        .mensaje-meta {
            margin-top: 15px;
            font-size: 12px;
            color: #999;
        }
        
        .no-mensajes {
            text-align: center;
            padding: 60px;
            color: #999;
        }
        
        .no-mensajes i {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
        }
        
        .filtros {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filtros input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            flex: 1;
            min-width: 200px;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .mensaje-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php if (!$autenticado): ?>
        <div class="container">
            <div class="login-form">
                <h2>Acceso al Panel</h2>
                <?php if (isset($error_login)): ?>
                    <p class="error"><?php echo $error_login; ?></p>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Acceder</button>
                </form>
                <p style="margin-top: 20px; text-align: center;">
                    <a href="index.html" style="color: #3498db;">← Volver al formulario</a>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="header">
            <div class="header-content">
                <h1>Panel de Mensajes</h1>
                <div class="header-actions">
                    <span style="color: #bdc3c7;">Total: <?php echo $total_mensajes; ?> mensajes</span>
                    <a href="index.html" class="btn btn-primary">Ver Formulario</a>
                    <a href="?logout=1" class="btn btn-danger">Cerrar Sesión</a>
                </div>
            </div>
        </div>
        
        <div class="container">
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_mensajes; ?></div>
                    <div class="stat-label">Total de Mensajes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($mensajes, function($m) { return strtotime($m['fecha']) > strtotime('-24 hours'); })); ?></div>
                    <div class="stat-label">Últimas 24 horas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count(array_filter($mensajes, function($m) { return strtotime($m['fecha']) > strtotime('-7 days'); })); ?></div>
                    <div class="stat-label">Últimos 7 días</div>
                </div>
            </div>
            
            <div class="filtros">
                <input type="text" id="buscar" placeholder="Buscar por nombre, email o mensaje..." onkeyup="filtrarMensajes()">
            </div>
            
            <div class="mensajes-container">
                <?php if (empty($mensajes)): ?>
                    <div class="no-mensajes">
                        <i>📭</i>
                        <h3>No hay mensajes</h3>
                        <p>Aún no has recibido ningún mensaje de contacto.</p>
                    </div>
                <?php else: ?>
                    <div id="lista-mensajes">
                        <?php foreach ($mensajes as $mensaje): ?>
                            <div class="mensaje" data-busqueda="<?php echo strtolower($mensaje['nombre'] . ' ' . $mensaje['email'] . ' ' . $mensaje['mensaje']); ?>">
                                <div class="mensaje-header">
                                    <div class="mensaje-info">
                                        <div class="mensaje-nombre"><?php echo htmlspecialchars($mensaje['nombre']); ?></div>
                                        <div class="mensaje-email"><?php echo htmlspecialchars($mensaje['email']); ?></div>
                                        <div class="mensaje-fecha"><?php echo date('d/m/Y H:i', strtotime($mensaje['fecha'])); ?></div>
                                    </div>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este mensaje?');">
                                        <input type="hidden" name="eliminar_id" value="<?php echo $mensaje['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                                <?php if (!empty($mensaje['asunto'])): ?>
                                    <div class="mensaje-asunto"><?php echo htmlspecialchars($mensaje['asunto']); ?></div>
                                <?php endif; ?>
                                <div class="mensaje-contenido">
                                    <?php echo nl2br(htmlspecialchars($mensaje['mensaje'])); ?>
                                </div>
                                <div class="mensaje-meta">
                                    IP: <?php echo $mensaje['ip']; ?> | 
                                    ID: <?php echo $mensaje['id']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
            function filtrarMensajes() {
                const busqueda = document.getElementById('buscar').value.toLowerCase();
                const mensajes = document.querySelectorAll('.mensaje');
                
                mensajes.forEach(mensaje => {
                    const texto = mensaje.getAttribute('data-busqueda');
                    if (texto.includes(busqueda)) {
                        mensaje.style.display = 'block';
                    } else {
                        mensaje.style.display = 'none';
                    }
                });
            }
        </script>
    <?php endif; ?>
</body>
</html>
