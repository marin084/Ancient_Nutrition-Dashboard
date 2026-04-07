<?php
// test_connection.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

echo "<h1>Test de Conexión Servidor -> Supabase</h1>";

// 1. Verificar extensiones básicas
echo "<h2>1. Verificaciones de Entorno PHP</h2>";
echo "<ul>";
echo "<li>Versión PHP: " . phpversion() . "</li>";
echo "<li>Extension CURL habilitada: " . (function_exists('curl_init') ? '<span style="color:green">SI</span>' : '<span style="color:red">NO</span>') . "</li>";
echo "<li>Extension JSON habilitada: " . (function_exists('json_decode') ? '<span style="color:green">SI</span>' : '<span style="color:red">NO</span>') . "</li>";
echo "</ul>";

// 2. Probar conexión real
echo "<h2>2. Prueba de Conexión CURL</h2>";
echo "<p>Intentando conectar a: <strong>$supabaseUrl</strong></p>";

try {
    // Intentamos una petición simple a la raiz de la API (/rest/v1/) para ver si responde
    // Nota: Es normal que devuelva 404 o lista vacía, lo importante es que NO de error de CURL
    $url = $supabaseUrl . '/rest/v1/';
    
    $ch = curl_init();
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json'
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos
    curl_setopt($ch, CURLOPT_VERBOSE, true); // Verbose debugging

    // Capturar verbose log en variable
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrNo = curl_errno($ch);
    
    curl_close($ch);

    if ($curlErrNo) {
        echo "<div style='background:#f8d7da; padding:15px; border:1px solid #f5c2c7; color:#842029;'>";
        echo "<h3>❌ FALLÓ LA CONEXIÓN (Error CURL)</h3>";
        echo "<p><strong>Error #$curlErrNo:</strong> $curlError</p>";
        
        // Debug info extra
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        echo "<pre>Verbose Log:\n" . htmlspecialchars($verboseLog) . "</pre>";
        echo "</div>";
        
        echo "<p><strong>Posibles causas:</strong></p>";
        echo "<ul>";
        echo "<li>Firewall del servidor bloqueando conexiones salientes (Outbound connections).</li>";
        echo "<li>Problemas de resolución DNS en el servidor.</li>";
        echo "<li>Certificados SSL desactualizados en el servidor (SSL cacert).</li>";
        echo "</ul>";

    } else {
        echo "<div style='background:#d1e7dd; padding:15px; border:1px solid #badbcc; color:#0f5132;'>";
        echo "<h3>✅ CONEXIÓN EXITOSA</h3>";
        echo "<p><strong>Código HTTP:</strong> $httpCode</p>";
        echo "<p><strong>Respuesta (primeros 500 chars):</strong> " . htmlspecialchars(substr($response, 0, 500)) . "</p>";
        echo "</div>";
        
        if ($httpCode >= 400) {
            echo "<p style='color:orange'>Nota: La conexión funciona, pero la API devolvió un error (4xx/5xx). Esto suele ser credenciales incorrectas o endpoint inexistente, pero NO es un problema de red.</p>";
        }
    }

} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage();
}
?>
