<?php
// upload_guia.php
ob_start(); // Start buffering to catch any stray output
ini_set('display_errors', 0);
ini_set('memory_limit', '256M');

session_start();
require_once 'config.php';

header('Content-Type: application/json');

// 1. Check Session and Role
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user_role_id = $_SESSION['user_role'] ?? 0;
// Only role 3 (Guide Creator) and role 1 (Admin) can upload guides
if ($user_role_id != 3 && $user_role_id != 1) {
    http_response_code(403);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No tienes permisos para esta acción']);
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
$numero_guia = $_POST['numero_guia'] ?? '';

if (empty($pedido_id) || empty($pedido_display)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Datos de pedido incompletos']);
    exit;
}

if (empty($numero_guia)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Debe ingresar el número de guía']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No se recibió el archivo o hubo un error en la carga']);
    exit;
}

$file = $_FILES['file'];
$fileType = mime_content_type($file['tmp_name']);
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

if (!in_array($fileType, $allowedTypes)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido (solo imágenes y PDF)']);
    exit;
}

// Check file size (max 10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'El archivo es demasiado grande (máximo 10MB)']);
    exit;
}

// 3. Prepare for Upload & Convert to WebP (if image)
$timestamp = time();
$bucket = 'comprobantes';
$fileContent = '';
$sourceImage = null;

// If it's a PDF, keep it as PDF
if ($fileType === 'application/pdf') {
    $filename = $pedido_display . '_guia_' . $timestamp . '.pdf';
    $fileContent = file_get_contents($file['tmp_name']);
} else {
    // Convert images to WebP
    $filename = $pedido_display . '_guia_' . $timestamp . '.webp';

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
            $tmpFile = tempnam(sys_get_temp_dir(), 'guia_webp_');
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
                $filename = $pedido_display . '_guia_' . $timestamp . '.' . $extension;
                $fileType = mime_content_type($file['tmp_name']);
                log_app_error("WebP conversion failed for guia, using original format: $extension");
            }
            if ($tmpFile && file_exists($tmpFile)) @unlink($tmpFile);
        } else {
            // Fallback
            $fileContent = file_get_contents($file['tmp_name']);
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $pedido_display . '_guia_' . $timestamp . '.' . $extension;
        }
    } catch (Exception $e) {
        // If conversion fails, fallback to original
        if ($sourceImage)
            @imagedestroy($sourceImage);
        if (isset($tmpFile) && $tmpFile && file_exists($tmpFile)) @unlink($tmpFile);
        $fileContent = file_get_contents($file['tmp_name']);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $pedido_display . '_guia_' . $timestamp . '.' . $extension;
    }
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
        // If 404, try to create bucket 'guias-ccr'
        if ($httpCode == 404 || strpos($response, 'Bucket not found') !== false) {
            // Create Bucket
            $createEndpoint = '/bucket';
            $createBody = ['id' => $bucket, 'name' => $bucket, 'public' => true];

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
    $fullUrl = $supabaseUrl . '/storage/v1/object/public/' . $storagePath;
    $currentTimestamp = date('Y-m-d H:i:s'); // Costa Rica timezone already set in config.php

    if (defined('WEBHOOK_GUIA_CCR') && !empty(WEBHOOK_GUIA_CCR)) {
        try {
            $webhookUrl = WEBHOOK_GUIA_CCR;
            $params = [
                'pedido' => $pedido_display,
                'guia' => $numero_guia,
                'qr_ccr' => $fullUrl,
                'qr_ccr_por' => $_SESSION['user_email'] ?? 'Usuario',
                'qr_ccr_update' => $currentTimestamp
            ];

            // Use send_webhook helper from config.php
            send_webhook($webhookUrl, $params);

            log_app_error("Webhook enviado (Guía CCR) pedido $pedido_display, guía $numero_guia");

        } catch (Exception $e) {
            // Log but don't fail the upload
            log_app_error("Error enviando webhook guía CCR: " . $e->getMessage());
        }
    }

    ob_get_clean(); // Discard any buffered output
    echo json_encode(['success' => true, 'message' => 'Guía registrada exitosamente']);
    exit;

} catch (Exception $e) {
    error_log("Upload Guía Error: " . $e->getMessage());
    ob_get_clean(); // Discard any buffered output
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
