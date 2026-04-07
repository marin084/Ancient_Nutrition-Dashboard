<?php
// config.php
// Configuración de Supabase API (REST)
date_default_timezone_set('America/Costa_Rica');


// Definir credenciales de API
// Debes colocar aquí tu URL y tu ANON KEY de Supabase
$supabaseUrl = 'https://kzvgmcgirewwjscymarm.supabase.co'; 
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imt6dmdtY2dpcmV3d2pzY3ltYXJtIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjQwMDMzMzUsImV4cCI6MjA3OTU3OTMzNX0.QODFuJL0wC_ks4Jq4I729H96g4GtL4GnQgExxFrHILY';

// Webhooks
define('WEBHOOK_PAGO_VALIDADO',         'https://mariin.apps.n8nitro.com/webhook/validar-pago');
define('WEBHOOK_PAQUETE_ENTREGADO',     'https://mariin.apps.n8nitro.com/webhook/paquete-entregado');
define('WEBHOOK_INGRESO_FACTURA',       'https://mariin.apps.n8nitro.com/webhook/ingreso-factura');
define('WEBHOOK_GUIA_CCR',              'https://mariin.apps.n8nitro.com/webhook/guia-ccr');
define('WEBHOOK_CANCELACION_PEDIDO',    'https://mariin.apps.n8nitro.com/webhook/cancelacion-pedido');


// --- Configuración de Logs ---
// Habilitar el registro de errores en un archivo local 'app_errors.log'
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/app_errors.log');

// Función helper para logs personalizados
function log_app_error($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logEntry = "[$timestamp] [APP_LOG] $message $contextStr" . PHP_EOL;
    // Escribir al mismo archivo que los errores de PHP
    error_log($logEntry); 
}

function supabase_request($endpoint, $method = 'GET', $data = null) {
    global $supabaseUrl, $supabaseKey;

    $url = $supabaseUrl . '/rest/v1' . $endpoint;

    $ch = curl_init();
    
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: return=representation' // Para recibir los datos de vuelta en POST/PATCH
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $curlErr = curl_error($ch);
        log_app_error("Error cURL en request a $endpoint", ['error' => $curlErr]);
        throw new Exception('Error cURL: ' . $curlErr);
    }
    
    curl_close($ch);

    if ($httpCode >= 400) {
        log_app_error("Error API Supabase ($httpCode) en $endpoint", ['response' => $response]);
        throw new Exception("Error Supabase ($httpCode): " . $response);
    }

    return json_decode($response, true);
}

function supabase_storage_request($endpoint, $method = 'GET', $data = null) {
    global $supabaseUrl, $supabaseKey;

    $url = $supabaseUrl . '/storage/v1' . $endpoint;

    $ch = curl_init();
    
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json'
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $curlErr = curl_error($ch);
        log_app_error("Error cURL Storage en request a $endpoint", ['error' => $curlErr]);
        throw new Exception('Error cURL: ' . $curlErr);
    }
    
    curl_close($ch);

    if ($httpCode >= 400) {
        log_app_error("Error API Storage ($httpCode) en $endpoint", ['response' => $response]);
        throw new Exception("Error Supabase Storage ($httpCode): " . $response);
    }

    return json_decode($response, true);
}

function send_webhook($url, $data) {
    $ch = curl_init();
    
    $jsonData = json_encode($data);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        log_app_error("Error Webhook ($url)", ['error' => $error]);
        throw new Exception("Error sending webhook: " . $error);
    }
    
    curl_close($ch);
    
    if ($httpCode >= 400) {
        log_app_error("Error Webhook Response ($httpCode)", ['response' => $response]);
        // We might or might not throw exception depending on if we want to block flow.
        // Usually good to throw to alert caller.
        throw new Exception("Webhook failed with status $httpCode: $response");
    }
    
    return $response;
}
