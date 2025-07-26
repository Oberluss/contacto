<?php
/**
 * Ejemplo de integración con webhooks
 * Copia este archivo como webhook.php y personalízalo
 */

// Función para enviar notificación a Slack
function enviarSlack($webhook_url, $mensaje, $nombre, $email) {
    $payload = [
        'text' => 'Nuevo mensaje de contacto',
        'attachments' => [
            [
                'color' => '#4CAF50',
                'fields' => [
                    [
                        'title' => 'De',
                        'value' => "$nombre ($email)",
                        'short' => true
                    ],
                    [
                        'title' => 'Fecha',
                        'value' => date('d/m/Y H:i'),
                        'short' => true
                    ],
                    [
                        'title' => 'Mensaje',
                        'value' => $mensaje,
                        'short' => false
                    ]
                ]
            ]
        ]
    ];
    
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response === 'ok';
}

// Función para enviar notificación a Discord
function enviarDiscord($webhook_url, $mensaje, $nombre, $email, $asunto) {
    $payload = [
        'username' => 'Sistema de Contacto',
        'avatar_url' => 'https://i.imgur.com/oBPXx0D.png',
        'embeds' => [
            [
                'title' => 'Nuevo mensaje de contacto',
                'color' => 5025616, // Verde
                'fields' => [
                    [
                        'name' => '👤 Nombre',
                        'value' => $nombre,
                        'inline' => true
                    ],
                    [
                        'name' => '📧 Email',
                        'value' => $email,
                        'inline' => true
                    ],
                    [
                        'name' => '📋 Asunto',
                        'value' => $asunto ?: 'Sin asunto',
                        'inline' => false
                    ],
                    [
                        'name' => '💬 Mensaje',
                        'value' => substr($mensaje, 0, 1000),
                        'inline' => false
                    ]
                ],
                'timestamp' => date('c'),
                'footer' => [
                    'text' => 'Sistema de Contacto'
                ]
            ]
        ]
    ];
    
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpcode === 204;
}

// Función para enviar notificación a Telegram
function enviarTelegram($bot_token, $chat_id, $mensaje, $nombre, $email, $asunto) {
    $texto = "🔔 *Nuevo mensaje de contacto*\n\n";
    $texto .= "👤 *Nombre:* $nombre\n";
    $texto .= "📧 *Email:* $email\n";
    $texto .= "📋 *Asunto:* " . ($asunto ?: 'Sin asunto') . "\n\n";
    $texto .= "💬 *Mensaje:*\n$mensaje";
    
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $texto,
        'parse_mode' => 'Markdown'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['ok']) && $result['ok'];
}

// Función para enviar email HTML
function enviarEmailHTML($para, $nombre, $email, $asunto, $mensaje) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Sistema de Contacto <noreply@tusitio.com>" . "\r\n";
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { background: white; border-radius: 10px; padding: 30px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #4CAF50; color: white; padding: 20px; border-radius: 10px 10px 0 0; margin: -30px -30px 30px -30px; text-align: center; }
            .field { margin-bottom: 20px; }
            .label { color: #666; font-size: 14px; margin-bottom: 5px; }
            .value { color: #333; font-size: 16px; }
            .message { background: #f5f5f5; padding: 20px; border-radius: 5px; line-height: 1.6; }
            .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Nuevo Mensaje de Contacto</h2>
            </div>
            
            <div class="field">
                <div class="label">Nombre</div>
                <div class="value">' . htmlspecialchars($nombre) . '</div>
            </div>
            
            <div class="field">
                <div class="label">Email</div>
                <div class="value"><a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></div>
            </div>
            
            <div class="field">
                <div class="label">Asunto</div>
                <div class="value">' . htmlspecialchars($asunto ?: 'Sin asunto') . '</div>
            </div>
            
            <div class="field">
                <div class="label">Mensaje</div>
                <div class="message">' . nl2br(htmlspecialchars($mensaje)) . '</div>
            </div>
            
            <div class="field">
                <div class="label">Fecha</div>
                <div class="value">' . date('d/m/Y H:i') . '</div>
            </div>
            
            <div class="footer">
                <p>Este mensaje fue enviado desde el formulario de contacto de tu sitio web.</p>
                <p><a href="' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/ver-mensajes.php">Ver todos los mensajes</a></p>
            </div>
        </div>
    </body>
    </html>';
    
    return mail($para, "Nuevo mensaje de contacto: " . ($asunto ?: 'Sin asunto'), $html, $headers);
}

// Función para registrar en un archivo CSV
function registrarCSV($archivo, $datos) {
    $existe = file_exists($archivo);
    $fp = fopen($archivo, 'a');
    
    // Si el archivo no existe, escribir encabezados
    if (!$existe) {
        fputcsv($fp, ['ID', 'Fecha', 'Nombre', 'Email', 'Asunto', 'Mensaje', 'IP']);
    }
    
    fputcsv($fp, [
        $datos['id'],
        $datos['fecha'],
        $datos['nombre'],
        $datos['email'],
        $datos['asunto'],
        $datos['mensaje'],
        $datos['ip']
    ]);
    
    fclose($fp);
}

// Función para integración con Google Sheets (requiere API)
function enviarGoogleSheets($spreadsheet_id, $datos, $credentials_file) {
    // Requiere Google Client Library
    // composer require google/apiclient:^2.0
    
    /*
    require_once 'vendor/autoload.php';
    
    $client = new Google_Client();
    $client->setAuthConfig($credentials_file);
    $client->addScope(Google_Service_Sheets::SPREADSHEETS);
    
    $service = new Google_Service_Sheets($client);
    
    $values = [[
        $datos['fecha'],
        $datos['nombre'],
        $datos['email'],
        $datos['asunto'],
        $datos['mensaje']
    ]];
    
    $body = new Google_Service_Sheets_ValueRange([
        'values' => $values
    ]);
    
    $params = [
        'valueInputOption' => 'USER_ENTERED'
    ];
    
    $result = $service->spreadsheets_values->append(
        $spreadsheet_id,
        'A1',
        $body,
        $params
    );
    
    return $result->getUpdates()->getUpdatedCells() > 0;
    */
}

// Ejemplo de uso en procesar-contacto.php:
/*
// Después de guardar el mensaje exitosamente:
if ($config['webhook_url']) {
    $tipo_webhook = $config['webhook_type'] ?? 'discord';
    
    switch ($tipo_webhook) {
        case 'slack':
            enviarSlack($config['webhook_url'], $mensaje, $nombre, $email);
            break;
        case 'discord':
            enviarDiscord($config['webhook_url'], $mensaje, $nombre, $email, $asunto);
            break;
        case 'telegram':
            enviarTelegram($config['telegram_bot_token'], $config['telegram_chat_id'], $mensaje, $nombre, $email, $asunto);
            break;
    }
}

// Enviar email HTML
if ($config['send_notifications'] && $config['admin_email']) {
    enviarEmailHTML($config['admin_email'], $nombre, $email, $asunto, $mensaje);
}

// Registrar en CSV
if ($config['enable_csv_log']) {
    registrarCSV('mensajes.csv', $entrada);
}
*/
?>