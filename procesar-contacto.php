<?php
// Cargar configuración si existe
$config = [];
if (file_exists('config.php')) {
    $config = include 'config.php';
}

// Configuración con valores por defecto
$archivo_contactos = $config['contacts_file'] ?? 'contactos.txt';
$archivo_comentarios = $config['comments_file'] ?? 'comentarios.txt';
$admin_email = $config['admin_email'] ?? '';
$send_notifications = $config['send_notifications'] ?? false;
$rate_limit = $config['rate_limit'] ?? 10;
$blocked_ips = $config['blocked_ips'] ?? [];

// Headers para JSON
header('Content-Type: application/json');

// Función para sanitizar datos
function sanitizar($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

// Función para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Verificar si es POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Obtener IP del cliente
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';

// Verificar IP bloqueada
if (in_array($ip, $blocked_ips)) {
    echo json_encode([
        'success' => false,
        'message' => $config['messages']['blocked'] ?? 'Tu solicitud ha sido bloqueada.'
    ]);
    exit;
}

// Verificar rate limit
if ($rate_limit > 0 && file_exists($archivo_contactos)) {
    $contenido = file_get_contents($archivo_contactos);
    $mensajes_hoy = 0;
    $fecha_actual = date('Y-m-d');
    
    $mensajes_raw = explode("---SEPARADOR---", $contenido);
    foreach ($mensajes_raw as $mensaje_raw) {
        $mensaje_raw = trim($mensaje_raw);
        if (!empty($mensaje_raw)) {
            $mensaje = json_decode($mensaje_raw, true);
            if ($mensaje && 
                isset($mensaje['ip']) && 
                $mensaje['ip'] === $ip && 
                substr($mensaje['fecha'], 0, 10) === $fecha_actual) {
                $mensajes_hoy++;
            }
        }
    }
    
    if ($mensajes_hoy >= $rate_limit) {
        echo json_encode([
            'success' => false,
            'message' => $config['messages']['rate_limit'] ?? 'Has enviado demasiados mensajes. Por favor, intenta más tarde.'
        ]);
        exit;
    }
}

// Obtener y validar datos
$nombre = isset($_POST['nombre']) ? sanitizar($_POST['nombre']) : '';
$email = isset($_POST['email']) ? sanitizar($_POST['email']) : '';
$asunto = isset($_POST['asunto']) ? sanitizar($_POST['asunto']) : 'Sin asunto';
$mensaje = isset($_POST['mensaje']) ? sanitizar($_POST['mensaje']) : '';

// Validaciones
$errores = [];

if (empty($nombre)) {
    $errores[] = 'El nombre es requerido';
}

if (empty($email)) {
    $errores[] = $config['messages']['required_field'] ?? 'El email es requerido';
} elseif (!validarEmail($email)) {
    $errores[] = $config['messages']['invalid_email'] ?? 'El email no es válido';
}

if (empty($mensaje)) {
    $errores[] = 'El mensaje es requerido';
}

// Si hay errores, devolver respuesta
if (!empty($errores)) {
    echo json_encode([
        'success' => false,
        'message' => implode('. ', $errores)
    ]);
    exit;
}

// Preparar datos para guardar
$fecha = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
$navegador = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

// Crear entrada formateada
$entrada = [
    'id' => uniqid('msg_'),
    'fecha' => $fecha,
    'nombre' => $nombre,
    'email' => $email,
    'asunto' => $asunto,
    'mensaje' => $mensaje,
    'ip' => $ip,
    'navegador' => $navegador,
    'leido' => false
];

// Convertir a JSON
$entrada_json = json_encode($entrada, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Intentar guardar en archivo
try {
    // Crear archivo si no existe
    if (!file_exists($archivo_contactos)) {
        file_put_contents($archivo_contactos, '');
    }
    
    // Agregar entrada al archivo (cada línea es un JSON)
    $resultado = file_put_contents(
        $archivo_contactos, 
        $entrada_json . "\n---SEPARADOR---\n", 
        FILE_APPEND | LOCK_EX
    );
    
    if ($resultado === false) {
        throw new Exception('No se pudo guardar el mensaje');
    }
    
    // Guardar también un resumen en comentarios.txt
    $comentario = sprintf(
        "[%s] %s (%s): %s\n",
        $fecha,
        $nombre,
        $email,
        substr($mensaje, 0, 50) . (strlen($mensaje) > 50 ? '...' : '')
    );
    
    file_put_contents(
        $archivo_comentarios,
        $comentario,
        FILE_APPEND | LOCK_EX
    );
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => $config['messages']['success'] ?? '¡Mensaje enviado correctamente! Nos pondremos en contacto contigo pronto.'
    ]);
    
    // Enviar email de notificación al administrador si está configurado
    if ($send_notifications && !empty($admin_email) && function_exists('mail')) {
        $titulo = "Nuevo mensaje de contacto" . ($asunto ? ": $asunto" : '');
        $contenido = "Nombre: $nombre\n";
        $contenido .= "Email: $email\n";
        $contenido .= "Asunto: $asunto\n";
        $contenido .= "Fecha: $fecha\n";
        $contenido .= "IP: $ip\n\n";
        $contenido .= "Mensaje:\n$mensaje\n\n";
        $contenido .= "---\n";
        $contenido .= "Ver todos los mensajes: " . ($config['base_url'] ?? '') . "/ver-mensajes.php";
        
        $headers = "From: " . ($config['from_email'] ?? 'noreply@' . $_SERVER['HTTP_HOST']) . "\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        mail($admin_email, $titulo, $contenido, $headers);
    }
    
    // Integración con webhook si está configurado
    if (!empty($config['webhook_url']) && file_exists('webhook.php')) {
        include_once 'webhook.php';
        // El archivo webhook.php debe implementar la lógica específica
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $config['messages']['error'] ?? 'Error al guardar el mensaje. Por favor, intenta más tarde.'
    ]);
    
    // Log de error si está habilitado
    if (!empty($config['enable_logging']) && !empty($config['log_file'])) {
        error_log(date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n", 3, $config['log_file']);
    }
}
?>