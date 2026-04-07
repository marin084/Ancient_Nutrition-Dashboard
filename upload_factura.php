<?php
// upload_factura.php
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
$numero_factura = $_POST['numero_factura'] ?? '';

if (empty($pedido_id) || empty($pedido_display) || empty($numero_factura)) {
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

// Validate PDF
if ($fileType !== 'application/pdf') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Solo se permiten archivos PDF']);
    exit;
}

// Validate file size (10MB max)
if ($file['size'] > 10 * 1024 * 1024) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'El archivo es demasiado grande (máx. 10MB)']);
    exit;
}

// 3. Prepare for Upload
$timestamp = time();
$filename = $pedido_display . '_factura_' . $timestamp . '.pdf';
$bucket = 'comprobantes';
$storagePath = "facturas/$filename"; // Store in facturas subfolder

// Read file content
$fileContent = file_get_contents($file['tmp_name']);

// 4. Upload to Supabase Storage
try {
    // Endpoint: /object/{bucket}/{path}
    $endpoint = "/object/$bucket/$storagePath";
    
    $url = $supabaseUrl . '/storage/v1' . $endpoint;
    
    $ch = curl_init();
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/pdf',
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
        // If 404, try to create bucket 'comprobantes'
        if ($httpCode == 404 || strpos($response, 'Bucket not found') !== false) {
             // Create Bucket
             $createEndpoint = '/bucket';
             $createBody = ['id' => $bucket, 'name' => $bucket, 'public' => false];
             
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
    if (defined('WEBHOOK_INGRESO_FACTURA') && !empty(WEBHOOK_INGRESO_FACTURA)) {
        try {
            $webhookUrl = WEBHOOK_INGRESO_FACTURA;
            $params = [
                'pedido' => $pedido_display,
                'pedido_id' => $pedido_id,
                'numero_factura' => $numero_factura,
                'pdf_factura' => $storagePath,
                'email' => $_SESSION['user_email'] ?? 'Usuario',
                'timestamp' => time()
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($params))
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4); 
            
            $webhookResponse = curl_exec($ch);
            curl_close($ch);
            
            log_app_error("Webhook enviado (Factura) pedido $pedido_display, número: $numero_factura");
            
        } catch (Exception $e) {
            // Silenciosamente fallar webhook
            log_app_error("Error enviando webhook factura: " . $e->getMessage());
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
