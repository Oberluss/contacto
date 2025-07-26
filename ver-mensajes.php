<?php
session_start();

// Configuración de acceso - Cambia estas credenciales
$usuario_correcto = "Oberlus";
$contraseña_correcta = "Admin2018!";

// Ruta al archivo de mensajes
$archivo_mensajes = "contactos.txt";

// Verificar si hay una sesión activa
$autenticado = isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true;

// Manejar el intento de inicio de sesión
if (isset($_POST['login'])) {
    $usuario = isset($_POST['usuario']) ? $_POST['usuario'] : '';
    $contraseña = isset($_POST['password']) ? $_POST['password'] : '';
    
    if ($usuario === $usuario_correcto && $contraseña === $contraseña_correcta) {
        $_SESSION['autenticado'] = true;
        $autenticado = true;
    } else {
        $error_login = "Usuario o contraseña incorrectos";
    }
}

// Manejar el cierre de sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ver-mensajes.php");
    exit();
}

// Manejar la confirmación de eliminación
if ($autenticado && isset($_GET['confirmar_eliminar']) && is_numeric($_GET['confirmar_eliminar'])) {
    $indice_a_confirmar = (int)$_GET['confirmar_eliminar'];
    $mensajes_a_confirmar = leer_mensajes($archivo_mensajes);
    
    if (isset($mensajes_a_confirmar[$indice_a_confirmar])) {
        $confirmacion_msg = true;
        $indice_confirmacion = $indice_a_confirmar;
    }
}

// Manejar la visualización de un mensaje específico
if ($autenticado && isset($_GET['ver']) && is_numeric($_GET['ver'])) {
    $indice_ver = (int)$_GET['ver'];
    $mensajes_vista = leer_mensajes($archivo_mensajes);
    
    if (isset($mensajes_vista[$indice_ver])) {
        $ver_mensaje = true;
        $indice_mensaje_ver = $indice_ver;
        
        // Actualizar estado a "Visto" si estaba pendiente
        if ($mensajes_vista[$indice_ver]['Estado'] === 'Pendiente') {
            actualizar_estado_mensaje($archivo_mensajes, $indice_ver, 'Visto');
            // Recargar los mensajes para actualizar la vista
            $mensajes = leer_mensajes($archivo_mensajes);
        }
    }
}

// Manejar el cambio manual de estado
if ($autenticado && isset($_GET['cambiar_estado']) && is_numeric($_GET['cambiar_estado'])) {
    $indice_cambiar = (int)$_GET['cambiar_estado'];
    $mensajes_estado = leer_mensajes($archivo_mensajes);
    
    if (isset($mensajes_estado[$indice_cambiar])) {
        $nuevo_estado = ($mensajes_estado[$indice_cambiar]['Estado'] === 'Visto') ? 'Pendiente' : 'Visto';
        actualizar_estado_mensaje($archivo_mensajes, $indice_cambiar, $nuevo_estado);
        header("Location: ver-mensajes.php?estado_actualizado=1");
        exit();
    }
}

// Manejar la eliminación de mensajes
if ($autenticado && isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $indice_eliminar = (int)$_GET['eliminar'];
    $mensajes_actuales = leer_mensajes($archivo_mensajes);
    
    if (isset($mensajes_actuales[$indice_eliminar])) {
        // Eliminar el mensaje del array
        array_splice($mensajes_actuales, $indice_eliminar, 1);
        
        // Reconstruir el contenido del archivo
        $nuevo_contenido = "";
        foreach (array_reverse($mensajes_actuales) as $m) {
            $nuevo_contenido .= "---NUEVO MENSAJE---\n";
            foreach ($m as $clave => $valor) {
                $nuevo_contenido .= "$clave: $valor\n";
            }
            $nuevo_contenido .= "\n";
        }
        
        // Guardar el archivo actualizado
        file_put_contents($archivo_mensajes, $nuevo_contenido);
        
        // Redireccionar para evitar reenvíos
        header("Location: ver-mensajes.php?eliminado=1");
        exit();
    }
}

// Manejar el borrado de todos los mensajes
if ($autenticado && isset($_GET['borrar_todos']) && $_GET['borrar_todos'] == 1) {
    // Vaciar el archivo
    file_put_contents($archivo_mensajes, "");
    
    // Redireccionar
    header("Location: ver-mensajes.php?eliminados_todos=1");
    exit();
}

// Función para leer los mensajes del archivo
function leer_mensajes($archivo) {
    if (!file_exists($archivo)) {
        return [];
    }
    
    $contenido = file_get_contents($archivo);
    $mensajes = [];
    
    // Dividir el contenido por separadores de mensaje (asumiendo formato específico)
    $entradas = explode("---NUEVO MENSAJE---", $contenido);
    
    foreach ($entradas as $entrada) {
        if (empty(trim($entrada))) {
            continue;
        }
        
        // Procesar cada mensaje para extraer los datos
        $mensaje = [];
        $lineas = explode("\n", trim($entrada));
        
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;
            
            // Buscar el separador entre clave y valor
            $pos = strpos($linea, ":");
            if ($pos !== false) {
                $clave = trim(substr($linea, 0, $pos));
                $valor = trim(substr($linea, $pos + 1));
                $mensaje[$clave] = $valor;
            } else {
                // Si no hay separador, podría ser parte del mensaje
                if (isset($mensaje['Mensaje'])) {
                    $mensaje['Mensaje'] .= "\n" . $linea;
                } else {
                    $mensaje['Contenido'] = $linea;
                }
            }
        }
        
        // Establecer estado por defecto como "Pendiente" si no existe
        if (!isset($mensaje['Estado'])) {
            $mensaje['Estado'] = 'Pendiente';
        }
        
        if (!empty($mensaje)) {
            $mensajes[] = $mensaje;
        }
    }
    
    return array_reverse($mensajes); // Mostrar los más recientes primero
}

// Función para actualizar el estado de un mensaje
function actualizar_estado_mensaje($archivo, $indice, $nuevo_estado) {
    $mensajes = leer_mensajes($archivo);
    
    if (isset($mensajes[$indice])) {
        // Actualizar el estado
        $mensajes[$indice]['Estado'] = $nuevo_estado;
        
        // Reconstruir el contenido del archivo
        $nuevo_contenido = "";
        foreach (array_reverse($mensajes) as $m) {
            $nuevo_contenido .= "---NUEVO MENSAJE---\n";
            foreach ($m as $clave => $valor) {
                $nuevo_contenido .= "$clave: $valor\n";
            }
            $nuevo_contenido .= "\n";
        }
        
        // Guardar el archivo actualizado
        file_put_contents($archivo, $nuevo_contenido);
        
        return true;
    }
    
    return false;
}

// Obtener mensajes si el usuario está autenticado
$mensajes = $autenticado ? leer_mensajes($archivo_mensajes) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Mensajes</title>
    <style>
        /* Estilos básicos */
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            background: linear-gradient(135deg, #7209b7 0%, #3a0ca3 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        main {
            padding: 2rem 0;
        }
        
        .login-form, .message-panel {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        button {
            background: #4361ee;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
        }
        
        button:hover {
            background: #3a0ca3;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .message-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #4361ee;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .message-content {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
            white-space: pre-line;
        }
        
        .message-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .logout-link {
            display: inline-block;
            margin-top: 1rem;
            color: #4361ee;
            text-decoration: none;
        }
        
        .logout-link:hover {
            text-decoration: underline;
        }
        
        .home-link {
            display: inline-block;
            margin-top: 1rem;
            color: #28a745;
            text-decoration: none;
            font-weight: bold;
        }
        
        .home-link:hover {
            text-decoration: underline;
        }
        
        .delete-all-link {
            display: inline-block;
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
        }
        
        .delete-all-link:hover {
            text-decoration: underline;
        }
        
        .delete-link {
            display: inline-block;
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .delete-link:hover {
            background: #c82333;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .confirmation-box {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .cancel-button, .confirm-button {
            display: inline-block;
            text-decoration: none;
            padding: 5px 15px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        
        .cancel-button {
            background-color: #6c757d;
        }
        
        .cancel-button:hover {
            background-color: #5a6268;
        }
        
        .confirm-button {
            background-color: #dc3545;
        }
        
        .confirm-button:hover {
            background-color: #c82333;
        }
        
        /* Estilos para la tabla de mensajes */
        .message-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .message-table th, .message-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .message-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .message-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .status-seen {
            background-color: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        
        .action-button {
            display: inline-block;
            padding: 5px 10px;
            margin-right: 5px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            color: white;
        }
        
        .view-button {
            background-color: #17a2b8;
        }
        
        .view-button:hover {
            background-color: #138496;
        }
        
        .status-button {
            background-color: #6c757d;
        }
        
        .status-button:hover {
            background-color: #5a6268;
        }
        
        .message-detail {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .message-detail h3 {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .detail-section {
            margin-bottom: 15px;
        }
        
        .detail-section strong {
            display: inline-block;
            width: 120px;
            color: #495057;
        }
        
        .message-content-detail {
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            white-space: pre-line;
        }
        
        .back-button {
            display: inline-block;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .back-button:hover {
            background-color: #5a6268;
        }
        
        .no-messages {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        footer {
            background: #1a1a2e;
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Panel de Mensajes </h1>
            <p>Gestión de mensajes de contacto</p>
        </div>
    </header>
    
    <main class="container">
        <?php if (!$autenticado): ?>
            <!-- Formulario de login -->
            <section class="login-form">
                <h2>Acceso al panel de mensajes</h2>
                
                <?php if (isset($error_login)): ?>
                    <div class="error-message"><?php echo $error_login; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="login">Iniciar sesión</button>
                </form>
            </section>
        <?php else: ?>
            <!-- Panel de mensajes -->
            <section class="message-panel">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Mensajes recibidos</h2>
                    <div>
                        <a href="index.html" class="home-link" style="margin-right: 15px;">Inicio</a>
                        <a href="?logout=1" class="logout-link" style="margin-right: 15px;">Cerrar sesión</a>
                        <?php if (!empty($mensajes)): ?>
                            <a href="?borrar_todos=1" class="delete-all-link" onclick="return confirm('¿Estás seguro de que quieres eliminar TODOS los mensajes? Esta acción no se puede deshacer.');">Borrar todos</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (isset($_GET['eliminado'])): ?>
                    <div class="success-message" style="display: block; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                        El mensaje ha sido eliminado correctamente.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['eliminados_todos'])): ?>
                    <div class="success-message" style="display: block; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                        Todos los mensajes han sido eliminados correctamente.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['estado_actualizado'])): ?>
                    <div class="success-message" style="display: block; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                        El estado del mensaje ha sido actualizado correctamente.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($ver_mensaje) && $ver_mensaje === true): ?>
                    <!-- Visualización detallada de un mensaje específico -->
                    <div class="message-detail">
                        <h3>Detalles del mensaje</h3>
                        
                        <div class="detail-section">
                            <strong>Asunto:</strong> 
                            <?php 
                            echo isset($mensajes[$indice_mensaje_ver]['Asunto']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['Asunto']) : 
                                 (isset($mensajes[$indice_mensaje_ver]['asunto']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['asunto']) : 'Sin asunto'); 
                            ?>
                        </div>
                        
                        <div class="detail-section">
                            <strong>Nombre:</strong> 
                            <?php 
                            echo isset($mensajes[$indice_mensaje_ver]['Nombre']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['Nombre']) : 
                                 (isset($mensajes[$indice_mensaje_ver]['nombre']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['nombre']) : 'No especificado'); 
                            ?>
                        </div>
                        
                        <div class="detail-section">
                            <strong>Email:</strong> 
                            <?php 
                            echo isset($mensajes[$indice_mensaje_ver]['Email']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['Email']) : 
                                 (isset($mensajes[$indice_mensaje_ver]['email']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['email']) : 'No especificado'); 
                            ?>
                        </div>
                        
                        <?php if (isset($mensajes[$indice_mensaje_ver]['IP']) || isset($mensajes[$indice_mensaje_ver]['ip'])): ?>
                        <div class="detail-section">
                            <strong>IP:</strong> 
                            <?php 
                            echo isset($mensajes[$indice_mensaje_ver]['IP']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['IP']) : 
                                 htmlspecialchars($mensajes[$indice_mensaje_ver]['ip']); 
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($mensajes[$indice_mensaje_ver]['Fecha']) || isset($mensajes[$indice_mensaje_ver]['fecha'])): ?>
                        <div class="detail-section">
                            <strong>Fecha:</strong> 
                            <?php 
                            echo isset($mensajes[$indice_mensaje_ver]['Fecha']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['Fecha']) : 
                                 htmlspecialchars($mensajes[$indice_mensaje_ver]['fecha']); 
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-section">
                            <strong>Estado:</strong> 
                            <span class="<?php echo $mensajes[$indice_mensaje_ver]['Estado'] === 'Pendiente' ? 'status-pending' : 'status-seen'; ?>">
                                <?php echo htmlspecialchars($mensajes[$indice_mensaje_ver]['Estado']); ?>
                            </span>
                            <a href="?cambiar_estado=<?php echo $indice_mensaje_ver; ?>" class="status-button action-button" style="margin-left: 10px;">
                                Cambiar estado
                            </a>
                        </div>
                        
                        <div class="detail-section">
                            <strong>Mensaje:</strong>
                            <div class="message-content-detail">
                                <?php 
                                echo isset($mensajes[$indice_mensaje_ver]['Mensaje']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['Mensaje']) : 
                                     (isset($mensajes[$indice_mensaje_ver]['mensaje']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['mensaje']) : 
                                     (isset($mensajes[$indice_mensaje_ver]['Contenido']) ? htmlspecialchars($mensajes[$indice_mensaje_ver]['Contenido']) : 'Sin contenido')); 
                                ?>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; display: flex; justify-content: space-between;">
                            <a href="ver-mensajes.php" class="back-button">
                                Volver a la lista
                            </a>
                            <a href="?confirmar_eliminar=<?php echo $indice_mensaje_ver; ?>" class="delete-link">
                                Eliminar mensaje
                            </a>
                        </div>
                        
                        <?php if (isset($confirmacion_msg) && $indice_confirmacion === $indice_mensaje_ver): ?>
                        <div class="confirmation-box" style="margin-top: 15px;">
                            <p style="font-weight: bold; margin-bottom: 10px;">¿Estás seguro de que deseas eliminar este mensaje?</p>
                            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                                <a href="?ver=<?php echo $indice_mensaje_ver; ?>" class="cancel-button">Cancelar</a>
                                <a href="?eliminar=<?php echo $indice_mensaje_ver; ?>" class="confirm-button">Confirmar</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                
                <?php else: ?>
                    <!-- Visualización en formato de tabla para la lista de mensajes -->
                    <?php if (empty($mensajes)): ?>
                        <div class="no-messages">
                            <p>No hay mensajes recibidos todavía.</p>
                        </div>
                    <?php else: ?>
                        <table class="message-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Asunto</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensajes as $indice => $mensaje): ?>
                                <tr>
                                    <td><?php echo count($mensajes) - $indice; ?></td>
                                    <td>
                                        <?php 
                                        echo isset($mensaje['Nombre']) ? htmlspecialchars($mensaje['Nombre']) : 
                                             (isset($mensaje['nombre']) ? htmlspecialchars($mensaje['nombre']) : 'No especificado'); 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo isset($mensaje['Asunto']) ? htmlspecialchars($mensaje['Asunto']) : 
                                             (isset($mensaje['asunto']) ? htmlspecialchars($mensaje['asunto']) : 'Sin asunto'); 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo isset($mensaje['Fecha']) ? htmlspecialchars($mensaje['Fecha']) : 
                                             (isset($mensaje['fecha']) ? htmlspecialchars($mensaje['fecha']) : 'No especificada'); 
                                        ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $mensaje['Estado'] === 'Pendiente' ? 'status-pending' : 'status-seen'; ?>">
                                            <?php echo htmlspecialchars($mensaje['Estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?ver=<?php echo $indice; ?>" class="view-button action-button">Ver</a>
                                        <a href="?cambiar_estado=<?php echo $indice; ?>" class="status-button action-button">Estado</a>
                                        <a href="?confirmar_eliminar=<?php echo $indice; ?>" class="delete-link action-button">Eliminar</a>
                                    </td>
                                </tr>
                                <?php if (isset($confirmacion_msg) && $indice_confirmacion === $indice): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="confirmation-box">
                                            <p style="font-weight: bold; margin-bottom: 10px;">¿Estás seguro de que deseas eliminar este mensaje?</p>
                                            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                                                <a href="ver-mensajes.php" class="cancel-button">Cancelar</a>
                                                <a href="?eliminar=<?php echo $indice; ?>" class="confirm-button">Confirmar</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; 2025 - Todos los derechos reservados</p>
            <p>Diseñado con ❤ por Oberlus</p>
        </div>
    </footer>
</body>
</html>
