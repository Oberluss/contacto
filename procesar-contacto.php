<?php
// Archivo para procesar el formulario de contacto

// Validar que el formulario fue enviado por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger los datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $asunto = isset($_POST['asunto']) ? trim($_POST['asunto']) : '';
    $mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
    
    // Validación básica
    if (empty($nombre) || empty($email) || empty($mensaje)) {
        // Redireccionar con error si falta algún campo obligatorio
        header("Location: index.html?status=error");
        exit();
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.html?status=error");
        exit();
    }
    
    // Preparar el contenido a guardar
    $fecha = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $contenido = "---NUEVO MENSAJE---\n";
    $contenido .= "Fecha: " . $fecha . "\n";
    $contenido .= "Nombre: " . $nombre . "\n";
    $contenido .= "Email: " . $email . "\n";
    $contenido .= "Asunto: " . $asunto . "\n";
    $contenido .= "IP: " . $ip . "\n";
    $contenido .= "Estado: Pendiente\n";
    $contenido .= "Mensaje: " . $mensaje . "\n\n";
    
    // Ruta del archivo donde se guardarán los mensajes
    $archivo = "contactos.txt";
    
    // Guardar el mensaje en el archivo
    $guardado = file_put_contents($archivo, $contenido, FILE_APPEND | LOCK_EX);
    
    if ($guardado !== false) {
        // Éxito al guardar
        header("Location: index.html?status=success");
    } else {
        // Error al guardar
        header("Location: index.html?status=error");
    }
} else {
    // Si alguien intenta acceder directamente a este archivo
    header("Location: index.html");
}
exit();