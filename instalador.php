<?php
/**
 * Instalador Automático desde GitHub
 * Descarga e instala el sistema de contacto desde el repositorio
 * 
 * Uso: Sube este archivo a la carpeta donde quieres instalar el sistema
 * y accede a él desde tu navegador.
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración del repositorio
$GITHUB_REPO = 'Oberluss/contacto';
$GITHUB_BRANCH = 'main'; // o 'master' si usas esa rama

// Función para descargar archivo
function descargarArchivo($url, $destino) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Installer');
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $data !== false) {
        return file_put_contents($destino, $data) !== false;
    }
    return false;
}

// Función para extraer ZIP
function extraerZip($archivo, $destino) {
    $zip = new ZipArchive;
    if ($zip->open($archivo) === TRUE) {
        $zip->extractTo($destino);
        $zip->close();
        return true;
    }
    return false;
}

// Procesar instalación
$paso = $_GET['paso'] ?? 'inicio';
$mensaje = '';
$error = '';

// Si ya está instalado
if (file_exists('.installed') && $paso != 'reinstalar') {
    $paso = 'ya_instalado';
}

// Procesar formulario de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $paso === 'configurar') {
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido';
    } else {
        // Actualizar contraseña en ver-mensajes.php
        if (file_exists('ver-mensajes.php')) {
            $contenido = file_get_contents('ver-mensajes.php');
            $contenido = preg_replace(
                '/\$password_admin = \'.*?\';/',
                '$password_admin = \'' . addslashes($password) . '\';',
                $contenido
            );
            file_put_contents('ver-mensajes.php', $contenido);
        }
        
        // Crear archivo de configuración
        $config = '<?php
return [
    \'site_title\' => \'' . addslashes($_POST['site_title'] ?? 'Mi Sitio Web') . '\',
    \'admin_email\' => \'' . addslashes($email) . '\',
    \'admin_password\' => \'' . addslashes($password) . '\',
    \'timezone\' => \'' . addslashes($_POST['timezone'] ?? date_default_timezone_get()) . '\',
    \'installed_date\' => \'' . date('Y-m-d H:i:s') . '\',
    \'send_notifications\' => ' . (isset($_POST['notifications']) ? 'true' : 'false') . '
];';
        
        file_put_contents('config.php', $config);
        file_put_contents('.installed', date('Y-m-d H:i:s'));
        
        // Crear archivos vacíos si no existen
        if (!file_exists('contactos.txt')) file_put_contents('contactos.txt', '');
        if (!file_exists('comentarios.txt')) file_put_contents('comentarios.txt', '');
        
        $paso = 'completado';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador desde GitHub - Sistema de Contacto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .header {
            background: #24292e;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .github-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.8;
            font-size: 14px;
        }
        
        .content {
            padding: 40px;
        }
        
        .step {
            margin-bottom: 30px;
        }
        
        .step-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .step-number {
            background: #4CAF50;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
        }
        
        .step-number.inactive {
            background: #ccc;
        }
        
        .step h2 {
            color: #333;
            font-size: 18px;
        }
        
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
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
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
        }
        
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn.secondary {
            background: #6c757d;
        }
        
        .btn.secondary:hover {
            background: #5a6268;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .file-list {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .file-item {
            padding: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .progress {
            background: #f0f0f0;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-bar {
            background: #4CAF50;
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            color: #e83e8c;
        }
        
        .links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            
            .links {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <svg class="github-icon" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
            </svg>
            <h1>Instalador desde GitHub</h1>
            <p>Sistema de Contacto - <?php echo $GITHUB_REPO; ?></p>
        </div>
        
        <div class="content">
            <?php if ($paso === 'inicio'): ?>
                <div class="step">
                    <div class="step-header">
                        <span class="step-number">1</span>
                        <h2>Bienvenido al Instalador</h2>
                    </div>
                    
                    <div class="status info">
                        <strong>📦 Este instalador:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Descargará los archivos desde GitHub</li>
                            <li>Los instalará en esta carpeta</li>
                            <li>Configurará el sistema automáticamente</li>
                        </ul>
                    </div>
                    
                    <p style="margin-bottom: 20px;">
                        <strong>Repositorio:</strong> 
                        <code>github.com/<?php echo $GITHUB_REPO; ?></code>
                    </p>
                    
                    <a href="?paso=descargar" class="btn">Comenzar Instalación</a>
                </div>
                
            <?php elseif ($paso === 'descargar'): ?>
                <div class="loading">
                    <div class="spinner"></div>
                    <h2>Descargando archivos...</h2>
                    <p>Por favor espera mientras descargamos los archivos desde GitHub</p>
                </div>
                
                <script>
                    setTimeout(function() {
                        window.location.href = '?paso=procesar';
                    }, 1000);
                </script>
                
            <?php elseif ($paso === 'procesar'): ?>
                <?php
                // Descargar el ZIP del repositorio
                $zipUrl = "https://github.com/{$GITHUB_REPO}/archive/refs/heads/{$GITHUB_BRANCH}.zip";
                $zipFile = 'temp_repo.zip';
                
                $descargaExitosa = false;
                $archivosDescargados = [];
                
                // Intentar descargar
                if (descargarArchivo($zipUrl, $zipFile)) {
                    // Extraer ZIP
                    if (extraerZip($zipFile, '.')) {
                        // Mover archivos desde la subcarpeta al directorio actual
                        $carpetaExtraccion = "contacto-{$GITHUB_BRANCH}";
                        
                        if (is_dir($carpetaExtraccion)) {
                            $archivos = [
                                'index.html',
                                'procesar-contacto.php',
                                'ver-mensajes.php',
                                'README.md',
                                '.gitignore'
                            ];
                            
                            foreach ($archivos as $archivo) {
                                $origen = $carpetaExtraccion . '/' . $archivo;
                                if (file_exists($origen)) {
                                    if (copy($origen, $archivo)) {
                                        $archivosDescargados[] = $archivo;
                                    }
                                }
                            }
                            
                            // Limpiar: eliminar carpeta temporal
                            $files = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($carpetaExtraccion, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::CHILD_FIRST
                            );
                            
                            foreach ($files as $fileinfo) {
                                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                                $todo($fileinfo->getRealPath());
                            }
                            
                            rmdir($carpetaExtraccion);
                            $descargaExitosa = true;
                        }
                    }
                    
                    // Eliminar ZIP
                    @unlink($zipFile);
                }
                ?>
                
                <div class="step">
                    <div class="step-header">
                        <span class="step-number">2</span>
                        <h2>Descarga de Archivos</h2>
                    </div>
                    
                    <?php if ($descargaExitosa && count($archivosDescargados) > 0): ?>
                        <div class="status success">
                            ✅ <strong>Archivos descargados correctamente!</strong>
                        </div>
                        
                        <div class="file-list">
                            <strong>Archivos instalados:</strong>
                            <?php foreach ($archivosDescargados as $archivo): ?>
                                <div class="file-item">✓ <?php echo $archivo; ?></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <a href="?paso=configurar" class="btn">Continuar con la Configuración</a>
                        </div>
                    <?php else: ?>
                        <div class="status error">
                            ❌ <strong>Error al descargar los archivos</strong>
                        </div>
                        
                        <div class="status warning">
                            <strong>Descarga manual:</strong>
                            <ol style="margin: 10px 0 0 20px;">
                                <li>Descarga los archivos desde: <a href="https://github.com/<?php echo $GITHUB_REPO; ?>" target="_blank">GitHub</a></li>
                                <li>Súbelos a esta carpeta</li>
                                <li>Vuelve a ejecutar este instalador</li>
                            </ol>
                        </div>
                        
                        <div class="status info">
                            <strong>Posibles causas del error:</strong>
                            <ul style="margin: 10px 0 0 20px;">
                                <li>El servidor no permite descargas externas (CURL deshabilitado)</li>
                                <li>No hay permisos de escritura en el directorio</li>
                                <li>El repositorio es privado o no existe</li>
                            </ul>
                        </div>
                        
                        <a href="?paso=inicio" class="btn secondary">Volver al Inicio</a>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($paso === 'configurar'): ?>
                <div class="step">
                    <div class="step-header">
                        <span class="step-number">3</span>
                        <h2>Configuración del Sistema</h2>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="status error">
                            ❌ <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="?paso=configurar">
                        <div class="form-group">
                            <label for="site_title">Título del Sitio</label>
                            <input type="text" id="site_title" name="site_title" value="Mi Sitio Web" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email del Administrador</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Contraseña del Panel (mínimo 8 caracteres)</label>
                            <input type="password" id="password" name="password" required minlength="8">
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">Zona Horaria</label>
                            <select id="timezone" name="timezone">
                                <option value="America/Mexico_City">Ciudad de México</option>
                                <option value="America/New_York">Nueva York</option>
                                <option value="America/Argentina/Buenos_Aires">Buenos Aires</option>
                                <option value="Europe/Madrid">Madrid</option>
                                <option value="UTC">UTC</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="notifications" name="notifications" checked>
                                <label for="notifications">Recibir notificaciones por email</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">Completar Instalación</button>
                    </form>
                </div>
                
            <?php elseif ($paso === 'completado'): ?>
                <div class="step">
                    <div class="step-header">
                        <span class="step-number">✓</span>
                        <h2>¡Instalación Completada!</h2>
                    </div>
                    
                    <div class="status success">
                        🎉 <strong>El sistema se ha instalado correctamente</strong>
                    </div>
                    
                    <p style="margin-bottom: 20px;">
                        Tu sistema de contacto está listo para usar. 
                        Puedes acceder a las siguientes secciones:
                    </p>
                    
                    <div class="links">
                        <a href="index.html" class="btn">Ver Formulario</a>
                        <a href="ver-mensajes.php" class="btn secondary">Panel de Admin</a>
                    </div>
                    
                    <div class="status warning" style="margin-top: 30px;">
                        <strong>⚠️ Importante:</strong> Por seguridad, elimina este archivo 
                        <code><?php echo basename(__FILE__); ?></code> después de la instalación.
                    </div>
                </div>
                
            <?php elseif ($paso === 'ya_instalado'): ?>
                <div class="step">
                    <div class="step-header">
                        <span class="step-number">!</span>
                        <h2>Sistema Ya Instalado</h2>
                    </div>
                    
                    <div class="status info">
                        El sistema ya está instalado en este directorio.
                    </div>
                    
                    <div class="links">
                        <a href="index.html" class="btn">Ir al Formulario</a>
                        <a href="ver-mensajes.php" class="btn secondary">Panel Admin</a>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee;">
                        <p style="color: #666; margin-bottom: 10px;">
                            Si deseas reinstalar el sistema:
                        </p>
                        <a href="?paso=reinstalar" class="btn secondary" 
                           onclick="return confirm('¿Estás seguro? Esto eliminará la configuración actual.')">
                            Reinstalar Sistema
                        </a>
                    </div>
                </div>
                
                <?php
                if ($paso === 'reinstalar') {
                    // Eliminar archivos de instalación
                    @unlink('.installed');
                    @unlink('config.php');
                    @unlink('contactos.txt');
                    @unlink('comentarios.txt');
                    header('Location: ?paso=inicio');
                    exit;
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
