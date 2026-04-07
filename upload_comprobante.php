<?php
// upload_comprobante.php
ob_start(); // Start buffering to catch any stray output
ini_set('display_errors', 0);
ini_set('memory_limit', '256M');

session_start();
require_once 'config.php';

header('Content-Type: application/json');

// 1. Check Session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// 2. Validate Inputs
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$pedido_id = $_POST['pedido_id'] ?? '';
$pedido_display = $_POST['pedido_display'] ?? '';

if (empty($pedido_id) || empty($pedido_display)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Datos de pedido incompletos']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No se recibió el archivo o hubo un error en la carga']);
    exit;
}

$file = $_FILES['file'];
$fileType = mime_content_type($file['tmp_name']);
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if (!in_array($fileType, $allowedTypes)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido (solo imágenes)']);
    exit;
}

// 3. Prepare for Upload & Convert to WebP
$timestamp = time();
$filename = $pedido_display . '_comprobante_entrega_' . $timestamp . '.webp';
$bucket = 'comprobantes';

// Convert to WebP
$fileContent = '';
$sourceImage = null;

try {
    switch ($fileType) {
        case 'image/jpeg':
            $sourceImage = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $sourceImage = @imagecreatefromwebp($file['tmp_name']);
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($file['tmp_name']);
            break;
    }

    if ($sourceImage) {
        // Write WebP to temp file to avoid output buffer conflicts
        $tmpFile = tempnam(sys_get_temp_dir(), 'comp_webp_');
        $webpSuccess = @imagewebp($sourceImage, $tmpFile, 80);
        imagedestroy($sourceImage);

        if ($webpSuccess && file_exists($tmpFile) && filesize($tmpFile) > 0) {
            $fileContent = file_get_contents($tmpFile);
            // Update content type for upload
            $fileType = 'image/webp';
        } else {
            // WebP conversion failed, fallback to original file
            $fileContent = file_get_contents($file['tmp_name']);
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = $pedido_display . '_comprobante_entrega_' . $timestamp . '.' . $extension;
            $fileType = mime_content_type($file['tmp_name']);
            log_app_error("WebP conversion failed for comprobante, using original format: $extension");
        }
        if ($tmpFile && file_exists($tmpFile)) @unlink($tmpFile);
    } else {
        // Fallback
        $fileContent = file_get_contents($file['tmp_name']);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $pedido_display . '_' . $timestamp . '.' . $extension;
    }
} catch (Exception $e) {
    // If conversion fails, fallback to original
    if ($sourceImage) @imagedestroy($sourceImage);
    if (isset($tmpFile) && $tmpFile && file_exists($tmpFile)) @unlink($tmpFile);
    $fileContent = file_get_contents($file['tmp_name']);
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $pedido_display . '_' . $timestamp . '.' . $extension;
}

// Safety check: abort if file content is empty
if (empty($fileContent) || strlen($fileContent) === 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Error: el archivo convertido está vacío. Intenta de nuevo.']);
    exit;
}
// 4. Upload to Supabase Storage
try {
    // Endpoint: /object/{bucket}/{path}
    $endpoint = "/object/$bucket/$filename";
    
    $url = $supabaseUrl . '/storage/v1' . $endpoint;
    
    $ch = curl_init();
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: ' . $fileType,
        'x-upsert: true' // Overwrite if exists
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Error cURL Upload: ' . curl_error($ch));
    }
    curl_close($ch);

    if ($httpCode >= 400) {
        // If 404, try to create bucket 'comprobantes' (same logic as signature upload)
        if ($httpCode == 404 || strpos($response, 'Bucket not found') !== false) {
             // Create Bucket
             $createEndpoint = '/bucket';
             $createBody = ['id' => $bucket, 'name' => $bucket, 'public' => false];
             
             // Simple curl for bucket creation reused from context or manually built
             // Using supabase_storage_request helper from config.php if suitable? 
             // config.php helper sends Content-Type: application/json which is fine for bucket create
             try {
                 supabase_storage_request($createEndpoint, 'POST', $createBody);
                 
                 // Retry Upload
                 $ch = curl_init();
                 curl_setopt($ch, CURLOPT_URL, $url);
                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                 curl_setopt($ch, CURLOPT_POST, true);
                 curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
                 $response = curl_exec($ch);
                 $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                 curl_close($ch);
                 
                 if ($httpCode >= 400) {
                      throw new Exception("Error Supabase Upload Retry ($httpCode): " . $response);
                 }
             } catch (Exception $e) {
                 throw new Exception("Error creando bucket/subiendo: " . $e->getMessage());
             }
        } else {
            throw new Exception("Error Supabase Upload ($httpCode): " . $response);
        }
    }

    // 5. Trigger Webhook
    $storagePath = "$bucket/$filename";
    $fullUrl = $supabaseUrl . '/storage/v1/object/public/' . $storagePath; // Assuming public url structure if public, or signed. 
    // Actually webhook usually expects full URL or path. 
    // The user example had 'imageUrl' => $imageUrl. 
    // We will send the storage path AND a construct of the URL just in case.
    // NOTE: If bucket is private, public URL won't work without token. 
    // But typically webhooks from backend might process it or generated signed url.
    // For now we send the path as main identifiers.
    
    if (defined('WEBHOOK_PAQUETE_ENTREGADO') && !empty(WEBHOOK_PAQUETE_ENTREGADO)) {
        try {
            $webhookUrl = WEBHOOK_PAQUETE_ENTREGADO;
            $params = [
                'pedido' => $pedido_display,
                'email' => $_SESSION['user_email'] ?? 'Usuario',
                'imageUrl' => $fullUrl, // Enviamos la URL pública
                'timestamp' => time()
            ];
            
            // Using the send_webhook helper from config.php si está disponible
            send_webhook($webhookUrl, $params);
            
            log_app_error("Webhook enviado (Comprobante) pedido $pedido_display");
            
        } catch (Exception $e) {
            // Silenciosamente fallar webhook
            log_app_error("Error enviando webhook comprobante: " . $e->getMessage());
        }
    }

    ob_get_clean(); // Discard any buffered output
    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    error_log("Upload Error: " . $e->getMessage());
    ob_get_clean(); // Discard any buffered output
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
