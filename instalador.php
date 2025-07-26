<?php
/**
 * Script de instalación del Sistema de Contacto
 * Ejecuta este archivo una sola vez para configurar el sistema
 */

session_start();

// Verificar si ya está instalado
$installed = file_exists('.installed');

// Procesar formulario de instalación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $errors = [];
    $success = false;
    
    // Validar datos
    $admin_password = $_POST['admin_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $site_title = $_POST['site_title'] ?? 'Mi Sitio Web';
    $timezone = $_POST['timezone'] ?? date_default_timezone_get();
    
    // Validaciones
    if (strlen($admin_password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres';
    }
    
    if ($admin_password !== $confirm_password) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido';
    }
    
    if (empty($errors)) {
        try {
            // Crear archivos necesarios
            $files_to_create = [
                'contactos.txt' => '',
                'comentarios.txt' => '',
                '.htaccess' => '# Proteger archivos sensibles
<FilesMatch "\.(txt|log)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Proteger config
<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>

# Proteger instalador
<Files "install.php">
    Order Allow,Deny
    Deny from all
</Files>

# Denegar acceso a archivos ocultos
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>'
            ];
            
            // Crear archivos
            foreach ($files_to_create as $file => $content) {
                if (!file_exists($file)) {
                    if (file_put_contents($file, $content) === false) {
                        throw new Exception("No se pudo crear el archivo: $file");
                    }
                }
            }
            
            // Crear directorio de backups
            if (!is_dir('backups')) {
                if (!mkdir('backups', 0755)) {
                    throw new Exception("No se pudo crear el directorio de backups");
                }
                // Proteger directorio de backups
                file_put_contents('backups/.htaccess', 'Deny from all');
            }
            
            // Actualizar ver-mensajes.php con la nueva contraseña
            if (file_exists('ver-mensajes.php')) {
                $contenido = file_get_contents('ver-mensajes.php');
                if ($contenido === false) {
                    throw new Exception("No se pudo leer ver-mensajes.php");
                }
                
                // Buscar y reemplazar la contraseña
                $patron = '/\$password_admin\s*=\s*[\'"].*?[\'"]\s*;/';
                $reemplazo = '$password_admin = \'' . addslashes($admin_password) . '\';';
                $contenido_nuevo = preg_replace($patron, $reemplazo, $contenido);
                
                if ($contenido_nuevo === null) {
                    throw new Exception("Error al procesar ver-mensajes.php");
                }
                
                if (file_put_contents('ver-mensajes.php', $contenido_nuevo) === false) {
                    throw new Exception("No se pudo actualizar ver-mensajes.php");
                }
            } else {
                throw new Exception("No se encontró el archivo ver-mensajes.php");
            }
            
            // Crear archivo de configuración principal
            $config_content = '<?php
/**
 * Archivo de configuración generado automáticamente
 * Fecha: ' . date('Y-m-d H:i:s') . '
 */

return [
    // Configuración general
    \'site_title\' => \'' . addslashes($site_title) . '\',
    \'admin_email\' => \'' . addslashes($admin_email) . '\',
    \'admin_password\' => \'' . addslashes($admin_password) . '\',
    \'timezone\' => \'' . addslashes($timezone) . '\',
    \'installed_date\' => \'' . date('Y-m-d H:i:s') . '\',
    \'version\' => \'1.0.0\',
    
    // Configuración de seguridad
    \'session_lifetime\' => 30,
    \'rate_limit\' => 10,
    \'blocked_ips\' => [],
    
    // Configuración de archivos
    \'contacts_file\' => \'contactos.txt\',
    \'comments_file\' => \'comentarios.txt\',
    \'backup_dir\' => \'backups/\',
    \'auto_backup\' => true,
    
    // Configuración de email
    \'send_notifications\' => ' . (isset($_POST['send_notifications']) ? 'true' : 'false') . ',
    \'notification_subject\' => \'Nuevo mensaje de contacto\',
    \'from_email\' => \'noreply@\' . $_SERVER[\'HTTP_HOST\'],
    \'from_name\' => \'' . addslashes($site_title) . '\',
    
    // Mensajes personalizados
    \'messages\' => [
        \'success\' => \'¡Mensaje enviado correctamente! Nos pondremos en contacto contigo pronto.\',
        \'error\' => \'Error al enviar el mensaje. Por favor, intenta más tarde.\',
        \'rate_limit\' => \'Has enviado demasiados mensajes. Por favor, intenta más tarde.\',
        \'blocked\' => \'Tu solicitud ha sido bloqueada.\',
        \'invalid_email\' => \'Por favor, ingresa un email válido.\',
        \'required_field\' => \'Este campo es requerido.\',
        \'message_too_short\' => \'El mensaje es demasiado corto.\',
        \'message_too_long\' => \'El mensaje es demasiado largo.\'
    ]
];';
            
            if (file_put_contents('config.php', $config_content) === false) {
                throw new Exception("No se pudo crear config.php");
            }
            
            // Marcar como instalado
            file_put_contents('.installed', date('Y-m-d H:i:s') . "\nInstalado por: " . $admin_email);
            
            // Configurar zona horaria
            date_default_timezone_set($timezone);
            
            $success = true;
            
        } catch (Exception $e) {
            $errors[] = 'Error durante la instalación: ' . $e->getMessage();
            
            // Intentar limpiar archivos creados si hay error
            if (file_exists('.installed')) {
                unlink('.installed');
            }
        }
    }
}

// Verificar requisitos del sistema
$requirements = [
    'PHP Version >= 7.0' => version_compare(PHP_VERSION, '7.0.0', '>='),
    'Directorio escribible' => is_writable('.'),
    'Función mail()' => function_exists('mail'),
    'JSON habilitado' => function_exists('json_encode'),
    'Sesiones habilitadas' => function_exists('session_start'),
    'Archivos necesarios' => file_exists('ver-mensajes.php') && file_exists('index.html') && file_exists('procesar-contacto.php')
];

// Lista de zonas horarias comunes
$timezones = [
    'America/Mexico_City' => 'Ciudad de México',
    'America/New_York' => 'Nueva York',
    'America/Chicago' => 'Chicago',
    'America/Los_Angeles' => 'Los Ángeles',
    'America/Argentina/Buenos_Aires' => 'Buenos Aires',
    'America/Bogota' => 'Bogotá',
    'America/Lima' => 'Lima',
    'America/Santiago' => 'Santiago',
    'Europe/Madrid' => 'Madrid',
    'Europe/London' => 'Londres',
    'Europe/Paris' => 'París',
    'Europe/Berlin' => 'Berlín',
    'UTC' => 'UTC'
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Contacto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .installer {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .header {
            background: #4CAF50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section h2::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 20px;
            background: #4CAF50;
            border-radius: 2px;
        }
        
        .requirements {
            list-style: none;
        }
        
        .requirement {
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        
        .requirement.success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .requirement.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .status-icon {
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        label .required {
            color: #e74c3c;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            height: 18px;
            width: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
        }
        
        .btn {
            background: #4CAF50;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #45a049;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert h3 {
            margin-bottom: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert ul {
            margin: 10px 0 0 20px;
        }
        
        .installed {
            text-align: center;
            padding: 60px 40px;
        }
        
        .installed .icon {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .installed h2 {
            color: #4CAF50;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .installed p {
            color: #666;
            margin-bottom: 30px;
            font-size: 18px;
        }
        
        .links {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .links a {
            color: #4CAF50;
            text-decoration: none;
            padding: 12px 24px;
            border: 2px solid #4CAF50;
            border-radius: 5px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .links a:hover {
            background: #4CAF50;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .progress {
            background: #f0f0f0;
            height: 4px;
            border-radius: 2px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-bar {
            background: #4CAF50;
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        @media (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            
            .links {
                flex-direction: column;
            }
            
            .links a {
                width: 100%;
                text-align: center;
            }
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 14px;
        }
        
        .strength-weak {
            color: #e74c3c;
        }
        
        .strength-medium {
            color: #f39c12;
        }
        
        .strength-strong {
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>🚀 Instalación del Sistema de Contacto</h1>
            <p>Configuración inicial del sistema</p>
        </div>
        
        <div class="content">
            <?php if ($installed): ?>
                <div class="installed">
                    <div class="icon">✅</div>
                    <h2>Sistema ya instalado</h2>
                    <p>El sistema de contacto ya está instalado y configurado correctamente.</p>
                    <div class="links">
                        <a href="index.html">Ver Formulario</a>
                        <a href="ver-mensajes.php">Panel Admin</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <h3>⚠️ Errores encontrados:</h3>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success">
                        <h3>✅ ¡Instalación completada!</h3>
                        <p>El sistema se ha instalado correctamente. Tu contraseña ha sido configurada.</p>
                    </div>
                    <div class="links">
                        <a href="index.html">Ver Formulario</a>
                        <a href="ver-mensajes.php">Acceder al Panel</a>
                    </div>
                <?php else: ?>
                    <div class="section">
                        <h2>Verificación de Requisitos</h2>
                        <ul class="requirements">
                            <?php foreach ($requirements as $req => $status): ?>
                                <li class="requirement <?php echo $status ? 'success' : 'error'; ?>">
                                    <span><?php echo $req; ?></span>
                                    <span class="status-icon"><?php echo $status ? '✓' : '✗'; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php
                    $all_requirements_met = !in_array(false, $requirements);
                    if ($all_requirements_met):
                    ?>
                        <div class="section">
                            <h2>Configuración del Sistema</h2>
                            <form method="POST" id="installForm">
                                <div class="form-group">
                                    <label for="site_title">Título del Sitio</label>
                                    <input 
                                        type="text" 
                                        id="site_title" 
                                        name="site_title" 
                                        value="<?php echo htmlspecialchars($_POST['site_title'] ?? 'Mi Sitio Web'); ?>" 
                                        required
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="admin_email">Email del Administrador <span class="required">*</span></label>
                                    <input 
                                        type="email" 
                                        id="admin_email" 
                                        name="admin_email" 
                                        value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>"
                                        required
                                    >
                                    <p class="help-text">Recibirás notificaciones de nuevos mensajes en este email</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="admin_password">Contraseña del Panel <span class="required">*</span></label>
                                    <input 
                                        type="password" 
                                        id="admin_password" 
                                        name="admin_password" 
                                        required 
                                        minlength="8"
                                        onkeyup="checkPasswordStrength(this.value)"
                                    >
                                    <div id="passwordStrength" class="password-strength"></div>
                                    <p class="help-text">Mínimo 8 caracteres. Esta será tu contraseña para acceder al panel de administración.</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirmar Contraseña <span class="required">*</span></label>
                                    <input 
                                        type="password" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        required 
                                        minlength="8"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="timezone">Zona Horaria</label>
                                    <select id="timezone" name="timezone">
                                        <?php 
                                        $current_tz = date_default_timezone_get();
                                        foreach ($timezones as $tz => $label): 
                                        ?>
                                            <option value="<?php echo $tz; ?>" <?php echo $tz === $current_tz ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="send_notifications" name="send_notifications" checked>
                                        <label for="send_notifications">
                                            Activar notificaciones por email cuando reciba nuevos mensajes
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="progress">
                                    <div class="progress-bar" id="progressBar"></div>
                                </div>
                                
                                <button type="submit" class="btn" id="installBtn">
                                    Instalar Sistema
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <h3>⚠️ Requisitos no cumplidos</h3>
                            <p>Por favor, corrige los requisitos marcados en rojo antes de continuar con la instalación.</p>
                            <ul style="margin-top: 10px;">
                                <li>Asegúrate de que PHP 7.0 o superior esté instalado</li>
                                <li>Verifica que el directorio tenga permisos de escritura (755 o 777)</li>
                                <li>Confirma que todos los archivos del sistema estén presentes</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Verificar fuerza de contraseña
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            let strength = 0;
            let message = '';
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            if (strength <= 2) {
                message = '⚠️ Contraseña débil';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength === 3) {
                message = '⚡ Contraseña media';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                message = '✅ Contraseña fuerte';
                strengthDiv.className = 'password-strength strength-strong';
            }
            
            strengthDiv.textContent = message;
        }
        
        // Validar formulario antes de enviar
        document.getElementById('installForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('admin_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const progressBar = document.getElementById('progressBar');
            const installBtn = document.getElementById('installBtn');
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden. Por favor, verifica e intenta nuevamente.');
                return;
            }
            
            // Mostrar progreso
            installBtn.textContent = 'Instalando...';
            installBtn.disabled = true;
            
            let progress = 0;
            const interval = setInterval(function() {
                progress += 10;
                progressBar.style.width = progress + '%';
                
                if (progress >= 90) {
                    clearInterval(interval);
                }
            }, 100);
        });
        
        // Auto-focus en el primer campo vacío
        window.addEventListener('load', function() {
            const firstEmpty = document.querySelector('input:not([value]):not([type="checkbox"])') || 
                             document.querySelector('input[value=""]:not([type="checkbox"])');
            if (firstEmpty) {
                firstEmpty.focus();
            }
        });
    </script>
</body>
</html>
