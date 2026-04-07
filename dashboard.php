<?php
// dashboard.php
session_start();
require_once 'config.php';
require_once 'includes/Permissions.php';

// 1. Verificación de seguridad
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_role_id = $_SESSION['user_role'] ?? 0;
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_email = $_SESSION['user_email'] ?? '';

$permissions = new Permissions($user_role_id);

// Lista de estados posibles global (antes línea 120)
$estados_posibles = [
    'PENDIENTE DE PAGO',
    'PAGO VALIDADO',
    'PENDIENTE DE GUIA',
    'LISTO PARA EMPACAR',
    'ENTREGADO',
    'FINALIZADO',
    'CANCELADO'
];

// Estados por defecto para admin: todos menos FINALIZADO
$estados_default_admin = array_values(array_filter($estados_posibles, fn($e) => $e !== 'FINALIZADO'));

// 2. Construcción de Query Params para la API según Rol
$endpoint = '/Pedidos?select=*';

switch ($user_role_id) {
    case 1:
        // Si no hay filtro o está vacío, aplicar el default (todos menos FINALIZADO)
        if (empty($_GET['filtro_estado']) && !isset($_GET['clear_filters'])) {
            $_GET['filtro_estado'] = $estados_default_admin;
        }

        // Si hay filtros seleccionados en el frontend (o asignados por defecto), aplicarlos
        if (!empty($_GET['filtro_estado']) && is_array($_GET['filtro_estado'])) {
            // Multiple estados selected - build OR condition
            $estados_filtrados = array_map('urlencode', $_GET['filtro_estado']);
            $or_conditions = array_map(function ($estado) {
                return "estado.eq.$estado";
            }, $estados_filtrados);
            $endpoint .= '&or=(' . implode(',', $or_conditions) . ')';
        }
        elseif (!empty($_GET['filtro_estado']) && !is_array($_GET['filtro_estado'])) {
            // Single estado (backward compatibility)
            $endpoint .= '&estado=eq.' . urlencode($_GET['filtro_estado']);
        }
        break;
    case 2:
        $endpoint .= '&or=(estado.eq.PENDIENTE%20DE%20PAGO,estado.eq.FINALIZADO)';
        break;
    case 3:
        $endpoint .= '&estado=eq.PENDIENTE%20DE%20GUIA';
        break;
    case 4:
        $cond1 = 'and(estado.eq.LISTO%20PARA%20EMPACAR,tipo_de_entrega.like.RETIRO%20EN%20CANAL*)';
        $cond2 = 'and(estado.eq.ENTREGADO,forma_de_pago.eq.PAGO%20EN%20DAT%C3%81FONO,referencia_pago.is.null)';
        $endpoint .= '&or=(' . $cond1 . ',' . $cond2 . ')';
        break;
    case 5:
        $endpoint .= '&estado=eq.ENTREGADO';
        break;
    case 6:
        $endpoint .= '&estado=eq.FINALIZADO';
        break;
    case 7:
        $endpoint .= '&estado=neq.FINALIZADO';
        break;
    case 8:
        $endpoint .= '&estado=eq.LISTO%20PARA%20EMPACAR&tipo_de_entrega=like.ENVIO%20CORREOS*';
        break;
    default:
        // No filter default
        break;
}

// ─── Filtro de rango de fechas o meses ───────────────────────────────────
$now = new DateTime('now', new DateTimeZone('America/Costa_Rica'));

// Roles 1 (Admin), 2 (Validador) y 6 (Contabilidad) usan filtro por fecha exacta (días)
if ($user_role_id == 1 || $user_role_id == 2 || $user_role_id == 6) {
    $default_fecha_fin = $now->format('Y-m-d');
    $default_fecha_inicio = (clone $now)->modify('-1 months')->format('Y-m-d');

    $fecha_inicio = $_GET['fecha_inicio'] ?? $default_fecha_inicio;
    $fecha_fin = $_GET['fecha_fin'] ?? $default_fecha_fin;
    $forma_pago = $_GET['forma_pago'] ?? '';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio))
        $fecha_inicio = $default_fecha_inicio;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin))
        $fecha_fin = $default_fecha_fin;

    $fecha_desde = $fecha_inicio;
    $fecha_hasta_dt = new DateTime($fecha_fin, new DateTimeZone('America/Costa_Rica'));
    $fecha_hasta_dt->modify('+1 day'); // Incluir todo el último día
    $fecha_hasta = $fecha_hasta_dt->format('Y-m-d');
}
else {
    // Otros roles usan filtro por mes
    $default_mes_fin = $now->format('Y-m');
    $default_mes_inicio = (clone $now)->modify('-1 months')->format('Y-m');

    $mes_inicio = $_GET['mes_inicio'] ?? $default_mes_inicio;
    $mes_fin = $_GET['mes_fin'] ?? $default_mes_fin;

    if (!preg_match('/^\d{4}-\d{2}$/', $mes_inicio))
        $mes_inicio = $default_mes_inicio;
    if (!preg_match('/^\d{4}-\d{2}$/', $mes_fin))
        $mes_fin = $default_mes_fin;

    $fecha_desde = $mes_inicio . '-01';
    $fecha_hasta_dt = new DateTime($mes_fin . '-01', new DateTimeZone('America/Costa_Rica'));
    $fecha_hasta_dt->modify('+1 month');
    $fecha_hasta = $fecha_hasta_dt->format('Y-m-d');
}

if ($user_role_id != 4) {
    $endpoint .= '&fecha_pedido=gte.' . urlencode($fecha_desde);
    $endpoint .= '&fecha_pedido=lt.' . urlencode($fecha_hasta);
}

// Filtro de método de pago (Roles 1, 2 y 6)
if (($user_role_id == 1 || $user_role_id == 2 || $user_role_id == 6) && !empty($forma_pago)) {
    $endpoint .= '&forma_de_pago=ilike.*' . urlencode(trim($forma_pago)) . '*';
}
// ────────────────────────────────────────────────────────────────────────

// Ordenar: Validador (role 2) prioriza PENDIENTE DE PAGO primero, luego por pedido desc
if ($user_role_id == 2) {
    // Supabase no soporta CASE en order, así que ordenamos en PHP después
    $endpoint .= '&order=pedido.desc';
} else {
    $endpoint .= '&order=pedido.desc';
}


try {
    $pedidos = supabase_request($endpoint, 'GET');
}
catch (Exception $e) {
    die("Error al cargar pedidos: " . $e->getMessage());
}

// Validador (role 2): PENDIENTE DE PAGO primero, luego el resto por pedido desc
if ($user_role_id == 2 && !empty($pedidos) && is_array($pedidos)) {
    usort($pedidos, function ($a, $b) {
        $a_pend = (strtoupper($a['estado'] ?? '') === 'PENDIENTE DE PAGO') ? 0 : 1;
        $b_pend = (strtoupper($b['estado'] ?? '') === 'PENDIENTE DE PAGO') ? 0 : 1;
        if ($a_pend !== $b_pend) {
            return $a_pend - $b_pend; // PENDIENTE DE PAGO first
        }
        return ($b['pedido'] ?? 0) - ($a['pedido'] ?? 0); // then by pedido desc
    });
}

// 3. Lógica de Columnas (igual que antes)
$is_contabilidad = ($user_role_id == 6);

// Helper para formatear fecha a GMT-6 (Costa Rica)
function format_date_cr($date_string)
{
    if (empty($date_string))
        return '--';
    try {
        // Asumimos que la fecha viene en UTC desde la BD
        $date = new DateTime($date_string, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone('America/Costa_Rica'));
        return $date->format('Y-m-d H:i:s');
    }
    catch (Exception $e) {
        return $date_string;
    }
}

// Extraer métodos de pago dinámicos de los pedidos actuales presentados
$metodos_pago_disponibles = [];
if (!empty($pedidos) && is_array($pedidos)) {
    foreach ($pedidos as $p) {
        if (!empty($p['forma_de_pago'])) {
            $metodos_pago_disponibles[$p['forma_de_pago']] = true;
        }
    }
}
$metodos_pago_disponibles = array_keys($metodos_pago_disponibles);
sort($metodos_pago_disponibles);

// Asegurar que el método filtrado esté en la lista aunque el filtrado retorne 0 resultados ahora (borde case)
if (!empty($forma_pago) && !in_array($forma_pago, $metodos_pago_disponibles)) {
    $metodos_pago_disponibles[] = $forma_pago;
    sort($metodos_pago_disponibles);
}

require_once 'dashboard_view.php';
