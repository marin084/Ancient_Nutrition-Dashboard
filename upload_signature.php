<?php
// upload_signature.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// 1. Check Session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// 2. Get Input
$input = json_decode(file_get_contents('php://input'), true);
$pedido_id = $input['pedido_id'] ?? '';
$pedido_display = $input['pedido_display'] ?? '';
$imageData = $input['image'] ?? '';

if (empty($pedido_id) || empty($imageData)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// 3. Process Image
// Remove header "data:image/png;base64,"
$imageParts = explode(';base64,', $imageData);
if (count($imageParts) < 2) {
    echo json_encode(['success' => false, 'error' => 'Formato de imagen inválido']);
    exit;
}
$imageBase64 = $imageParts[1];
$imageDecoded = base64_decode($imageBase64);

if ($imageDecoded === false) {
    echo json_encode(['success' => false, 'error' => 'Error al decodificar imagen']);
    exit;
}

// Generate Filename: signatures/{pedido_display}_timestamp.png
$filename = 'signatures/' . $pedido_display . '_' . time() . '.png';
$bucket = 'comprobantes';

// 4. Upload to Supabase Storage
try {
    // Check/Create bucket logic could go here, but for now try upload directly.
    // If bucket doesn't exist, we might get 404. 
    // We assume 'comprobantes' exists or we can create it. 
    // Since we saw empty bucket list, we'll try to create if upload fails?
    // Let's just try upload first.

    // Endpoint: /object/{bucket}/{path}
    $endpoint = "/object/$bucket/$filename";
    
    // We need to send binary body. supabase_storage_request handles JSON by default.
    // We need a raw POST. 'supabase_storage_request' wraps curl with JSON headers.
    // We need to write a custom upload or modify the helper.
    // The helper sets 'Content-Type: application/json'. This is BAD for image upload.
    // We should implement a local specific upload function or modify the request.
    
    // Custom Upload Logic Here
    $url = $supabaseUrl . '/storage/v1' . $endpoint;
    $ch = curl_init();
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: image/png',
        'x-upsert: true' // Overwrite if exists
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $imageDecoded);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Error cURL Upload: ' . curl_error($ch));
    }
    curl_close($ch);

    if ($httpCode >= 400) {
        // If 404, maybe bucket doesn't exist?
        // Try to create bucket 'comprobantes'
        if ($httpCode == 404 || strpos($response, 'Bucket not found') !== false) {
             // Create Bucket
             $createEndpoint = '/bucket';
             $createBody = ['id' => $bucket, 'name' => $bucket, 'public' => false];
             // reuse config helper for json
             try {
                 supabase_storage_request($createEndpoint, 'POST', $createBody);
                 // Retry Upload
                 $ch = curl_init();
                 curl_setopt($ch, CURLOPT_URL, $url);
                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                 curl_setopt($ch, CURLOPT_POST, true);
                 curl_setopt($ch, CURLOPT_POSTFIELDS, $imageDecoded);
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

    // 5. Update Order in Supabase
    // 5. Update Order in Supabase
    // Path to store: bucket/filename
    // Path to store: bucket/filename
    $storagePath = "$bucket/$filename";
    
    // DESHABILITADO POR SOLICITUD: El webhook se encargará de actualizar estado y columnas.
    /*
    $patchData = [
        'estado' => 'ENTREGADO',
        'entregado_cliente_comprobante' => $storagePath,
        'entregado_cliente_por' => $_SESSION['user_email'] ?? 'Usuario',
        'entregado_cliente_updated' => date('Y-m-d\TH:i:sP') // ISO 8601
    ];
    
    $updateEndpoint = "/Pedidos?id=eq.$pedido_id";
    $updateResponse = supabase_request($updateEndpoint, 'PATCH', $patchData);
    */

    // 5. Integración con Hoja de Entrega HTML (PRIMERO)
    // Verificar si existe html_hoja_entrega
    try {
        $checkEndpoint = '/Pedidos?id=eq.' . $pedido_id . '&select=html_hoja_entrega';
        $pedidos = supabase_request($checkEndpoint, 'GET');
        $htmlData = $pedidos[0]['html_hoja_entrega'] ?? null;

        if ($htmlData) {
            $newHtmlContent = null;
            $isContent = (strpos(trim($htmlData), '<') === 0);
            $htmlPath = null;
            $htmlContent = null;

            if ($isContent) {
                // Es contenido HTML directo
                $htmlContent = $htmlData;
            } else {
                // Es una ruta de archivo en Storage
                $htmlPath = $htmlData;
                $downloadUrl = $supabaseUrl . '/storage/v1/object/' . $htmlPath;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $downloadUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'apikey: ' . $supabaseKey,
                    'Authorization: Bearer ' . $supabaseKey
                ]);
                $htmlContent = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode != 200 || empty($htmlContent)) {
                    log_app_error("No se pudo descargar el HTML ($httpCode) de: $htmlPath");
                }
            }

            if (!empty($htmlContent)) {
                // B. Inyectar la Firma
                $searchMarker = '<div class="sign-label">FIRMA</div>';
                // Agregamos un estilo para que se ajuste a la celda
                $imgTag = '<br><img src="data:image/png;base64,' . $imageBase64 . '" style="max-width: 100%; max-height: 180px; display: block; margin-top: 5px;">';
                
                // Reemplazo simple: "marcador" -> "marcador + img"
                $newHtmlContent = str_replace($searchMarker, $searchMarker . $imgTag, $htmlContent);

                // B.2 Inyectar la Fecha
                $dateMarker = '<div class="sign-label">FECHA</div>';
                $dateTag = '<br><div class="value" style="margin-top: 5px; font-size: 14px;">' . date('Y-m-d H:i:s') . '</div>';
                $newHtmlContent = str_replace($dateMarker, $dateMarker . $dateTag, $newHtmlContent);

                // B.3 Inyectar "Entregado por"
                $deliveredBy = $_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'Usuario';
                log_app_error("Intentando inyectar 'Entregado por': " . $deliveredBy);
                
                // Múltiples patrones de búsqueda para mayor compatibilidad
                $patterns = [
                    // Patrón 1: Con div class="label" y class="value"
                    '/(<div class="label">Entregado por<\/div>\s*<div class="value">)(<\/div>)/',
                    // Patrón 2: Con div class="label" y class="value" (case insensitive en contenido)
                    '/(<div class=["\']label["\']>[Ee]ntregado por[:\s]*<\/div>\s*<div class=["\']value["\']>)(<\/div>)/',
                    // Patrón 3: Buscar simplemente "Entregado por:" seguido de espacio/salto
                    '/(Entregado por:\s*)(\n|<br>|$)/',
                    // Patrón 4: Buscar <p> o cualquier tag con "Entregado por:"
                    '/(<[^>]*>Entregado por:<\/[^>]*>\s*)(<[^>]*><\/[^>]*>|$)/',
                ];
                
                $injected = false;
                foreach ($patterns as $index => $pattern) {
                    $result = preg_replace($pattern, '$1' . $deliveredBy . '$2', $newHtmlContent);
                    if ($result !== $newHtmlContent) {
                        $newHtmlContent = $result;
                        $injected = true;
                        log_app_error("Patrón " . ($index + 1) . " funcionó para 'Entregado por'");
                        break;
                    }
                }
                
                if (!$injected) {
                    log_app_error("ADVERTENCIA: No se pudo inyectar 'Entregado por'. Buscando en HTML...");
                    // Log una muestra del HTML para debug
                    $searchPos = stripos($newHtmlContent, 'entregado por');
                    if ($searchPos !== false) {
                        $snippet = substr($newHtmlContent, max(0, $searchPos - 100), 300);
                        log_app_error("HTML cercano encontrado: " . $snippet);
                    } else {
                        log_app_error("No se encontró 'Entregado por' en el HTML");
                    }
                }

                // C. Guardar el HTML modificado
                if ($isContent) {
                    // Actualizar la columna en la BD
                    $updateHtmlData = ['html_hoja_entrega' => $newHtmlContent];
                    supabase_request('/Pedidos?id=eq.' . $pedido_id, 'PATCH', $updateHtmlData);
                    log_app_error("Firma inyectada en HTML (Database Row) para pedido $pedido_display");
                } else {
                    // Subir a Storage
                    $uploadHtmlUrl = $supabaseUrl . '/storage/v1/object/' . $htmlPath;
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $uploadHtmlUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'apikey: ' . $supabaseKey,
                        'Authorization: Bearer ' . $supabaseKey,
                        'Content-Type: text/html',
                        'x-upsert: true'
                    ]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $newHtmlContent);
                    $resHtml = curl_exec($ch);
                    $codeHtml = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($codeHtml >= 400) {
                        log_app_error("Error actualizando HTML hoja entrega ($codeHtml): " . $resHtml);
                    } else {
                        log_app_error("Firma inyectada en HTML (Storage) para pedido $pedido_display");
                    }
                }
            }
        }
    } catch (Exception $e) {
        // No detener el flujo principal si falla la inyección HTML
        error_log("Excepción inyectando firma en HTML: " . $e->getMessage());
    }

    // 6. TRIGGER WEBHOOK (DESPUÉS de actualizar HTML)
    if (defined('WEBHOOK_PAQUETE_ENTREGADO') && !empty(WEBHOOK_PAQUETE_ENTREGADO)) {
        try {
            $webhookData = [
                'pedido' => $pedido_display, // Número de pedido
                'email' => $_SESSION['user_email'] ?? 'Usuario',
                'imageUrl' => $storagePath,
                'timestamp' => time()
            ];
            send_webhook(WEBHOOK_PAQUETE_ENTREGADO, $webhookData);
            log_app_error("Webhook enviado para pedido $pedido_display", $webhookData);
        } catch (Exception $e) {
            log_app_error("Error enviando webhook firma: " . $e->getMessage());
            // No detenemos el flujo, ya completamos la actualización HTML
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Upload Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
