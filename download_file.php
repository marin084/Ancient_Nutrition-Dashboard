<?php
// download_file.php
session_start();
require_once 'config.php';

// Verificación de sesión
if (!isset($_SESSION['user_id'])) {
    die('No autorizado');
}

$fullPath = $_GET['path'] ?? '';

if (empty($fullPath)) {
    die('Ruta de archivo requerida');
}

$fullPath = trim($fullPath);
$fullPath = ltrim($fullPath, '/');

if (empty($fullPath)) {
    die('Ruta vacía');
}

$pathForUrl = str_replace(' ', '%20', $fullPath);

try {
    $endpoint = "/object/sign/" . $pathForUrl;
    $body = ['expiresIn' => 180];
    
    $responseKey = supabase_storage_request($endpoint, 'POST', $body);
    
    if (isset($responseKey['signedURL'])) {
        $signPath = $responseKey['signedURL'];
        if (strpos($signPath, '/') !== 0) {
            $signPath = '/' . $signPath;
        }

        $signedUrl = $supabaseUrl . '/storage/v1' . $signPath;
        header('Location: ' . $signedUrl);
        exit;
    } else {
        die('Error al obtener el enlace del archivo desde Supabase.');
    }

} catch (Exception $e) {
    die("Ocurrió un error: " . htmlspecialchars($e->getMessage()));
}
?>
