<?php
// ajax_get_signed_url.php
session_start();
require_once 'config.php';

// Verificación de sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'No autorizado']));
}

$input = json_decode(file_get_contents('php://input'), true);
$fullPath = $input['path'] ?? '';


if (empty($fullPath)) {
    http_response_code(400);
    die(json_encode(['error' => 'Ruta de archivo requerida']));
}

// Asumimos que $fullPath viene como "bucket/carpeta/archivo.pdf"
// Limpiamos la ruta
$fullPath = trim($fullPath);
$fullPath = ltrim($fullPath, '/');

// Validar que no esté vacía después de limpiar
if (empty($fullPath)) {
    http_response_code(400);
    die(json_encode(['error' => 'Ruta vacía']));
}

// Endpoint para firmar: /object/sign/{ruta_completa}
// NOTA: No hacemos rawurlencode() de toda la ruta porque eso codificaría los slashes '/',
// rompiendo la estructura bucket/archivo que espera Supabase.
// Supabase espera: /object/sign/bucket/folder/file.ext

// Intentamos codificar solo los espacios por si acaso, pero respetando la estructura.
// La mejor estrategia si no confiamos en la entrada es separar por / y codificar componentes,
// pero si "curl" funcionó con la string plana, probemos con la string plana primero, 
// asegurando que no haya caracteres inválidos simples.
$pathForUrl = str_replace(' ', '%20', $fullPath);

try {
    $endpoint = "/object/sign/" . $pathForUrl;
    
    // Debug info
    // log_app_error("Generando Signed URL", ['endpoint' => $endpoint]);

    $body = ['expiresIn' => 180];
    
    $responseKey = supabase_storage_request($endpoint, 'POST', $body);
    
    if (isset($responseKey['signedURL'])) {
        // CORRECCION IMPORTANTE:
        // La URL firmada devuelta es "/object/sign/..." sin el prefijo "/storage/v1".
        // Para que sea descargable, debe ser: domain + /storage/v1 + signedURL
        
        // Verificamos si signedURL ya empieza con slash
        $signPath = $responseKey['signedURL'];
        if (strpos($signPath, '/') !== 0) {
            $signPath = '/' . $signPath;
        }

        $signedUrl = $supabaseUrl . '/storage/v1' . $signPath; // Ajustado según especificación del usuario
        
        echo json_encode(['signedUrl' => $signedUrl]);
    } else {
        $msg = $responseKey['message'] ?? 'Error desconocido de Supabase';
        $err = $responseKey['error'] ?? 'No error code';
        
        // Log detailed error
        log_app_error("Error obteniendo Signed URL", [
            'endpoint' => $endpoint,
            'msg' => $msg,
            'err' => $err
        ]);

        echo json_encode([
            'error' => "Supabase Error: $err",
            'details' => $msg,
            'endpoint_tried' => $endpoint
        ]);
    }

} catch (Exception $e) {
    log_app_error("Excepción en ajax_get_signed_url", ['exception' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
