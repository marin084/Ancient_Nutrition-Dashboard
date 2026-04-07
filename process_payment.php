<?php
// process_payment.php
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
$pedido_display = $input['pedido_display'] ?? '';
$referencia = trim($input['referencia'] ?? '');
$comentario = trim($input['comentario'] ?? '');

if (empty($pedido_display) || empty($referencia)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // 3. Send Webhook INSTEAD of updating Supabase directly
    // Payload: numero (pedido), correo (user email), referencia, referencia_pago_comentario
    $data = [
        'numero' => $pedido_display,
        'correo' => $_SESSION['user_email'] ?? 'unknown',
        'referencia' => $referencia,
        'referencia_pago_comentario' => $comentario
    ];


    send_webhook(WEBHOOK_PAGO_VALIDADO, $data);
    
    // Return success. The DB update should be handled by the external webhook flow.
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Payment Process Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
