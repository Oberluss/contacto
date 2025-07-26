<?php
// ELIMINAR ESTE ARCHIVO DESPUÉS DE USAR
echo "<h2>Limpiando instalación anterior...</h2>";

$archivos = ['.installed', 'config.php', 'contactos.txt', 'comentarios.txt', '.htaccess'];

foreach($archivos as $archivo) {
    if(file_exists($archivo)) {
        if(unlink($archivo)) {
            echo "✅ Eliminado: $archivo<br>";
        } else {
            echo "❌ No se pudo eliminar: $archivo<br>";
        }
    } else {
        echo "ℹ️ No existe: $archivo<br>";
    }
}

// Eliminar directorio backups si existe
if(is_dir('backups')) {
    $files = glob('backups/*');
    foreach($files as $file) {
        if(is_file($file)) unlink($file);
    }
    if(rmdir('backups')) {
        echo "✅ Eliminado directorio: backups<br>";
    }
}

echo "<hr>";
echo "<h3>✨ Limpieza completada</h3>";
echo "<p><a href='install.php'>Ir al instalador</a></p>";
echo "<p style='color:red'>⚠️ ELIMINA ESTE ARCHIVO (clean.php) DESPUÉS DE USAR</p>";
?>