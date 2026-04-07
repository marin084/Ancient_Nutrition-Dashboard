<?php
// process_payment_entregado.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// 1. Verificación de seguridad
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$user_role_id = $_SESSION['user_role'] ?? 0;
// Permitir solo Rol 4 (Entrega)
if ($user_role_id != 4) {
    echo json_encode(['success' => false, 'error' => 'Permisos insuficientes']);
    exit;
}

$user_email = $_SESSION['user_email'] ?? 'Usuario';

// 2. Leer entrada JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$pedido_id = $data['pedido_id'] ?? '';
$pedido_display = $data['pedido_display'] ?? '';
$referencia = $data['referencia'] ?? '';

if (empty($pedido_id) || empty($referencia)) {
    echo json_encode(['success' => false, 'error' => 'Falta la referencia o ID del pedido']);
    exit;
}

// 3. Actualizar la base de datos (Supabase)
$update_endpoint = '/Pedidos?id=eq.' . urlencode($pedido_id);
$update_data = [
    'referencia_pago' => $referencia,
    'referencia_pago_por' => $user_email,
    'referencia_pago_update' => date('Y-m-d\TH:i:sP') // Current time in ISO8601 with timezone
];

try {
    $response = supabase_request($update_endpoint, 'PATCH', $update_data);
    
    // Asumiendo que si no hay excepción, fue exitoso. supabase_request devuelve la respuesta decodificada.
    
    // Registramos la acción en el log si fuera necesario, o simplemente retornamos éxito.
    echo json_encode([
        'success' => true,
        'message' => 'Referencia de pago guardada exitosamente'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al guardar en la base de datos: ' . $e->getMessage()
    ]);
}
?>
