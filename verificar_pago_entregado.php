<?php
// verificar_pago.php
session_start();
require_once 'config.php';

// 1. Verificación de seguridad y Deep Linking
if (!isset($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI']);
    header('Location: login.php?redirect=' . $redirect);
    exit;
}

$user_role_id = $_SESSION['user_role'] ?? 0;
// Permitir solo Rol 4 (Entrega)
if ($user_role_id != 4) {
    die("Acceso no autorizado.");
}

$pedido_id = $_GET['pedido'] ?? '';

if (empty($pedido_id)) {
    die("Pedido no especificado.");
}

// 2. Obtener datos del pedido
$endpoint = '/Pedidos?pedido=eq.' . urlencode($pedido_id) . '&select=*';
try {
    $pedidos = supabase_request($endpoint, 'GET');
    if (empty($pedidos)) {
        die("Pedido no encontrado.");
    }
    $pedido = $pedidos[0];
    
    // Validar estado (Opcional, pero buena práctica)
    // if (strpos(strtoupper($pedido['estado']), 'PENDIENTE') === false) {
    //    // Warning on UI?
    // }
} catch (Exception $e) {
    die("Error al cargar pedido: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Pago - Pedido <?php echo htmlspecialchars($pedido['pedido']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .payment-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .payment-icon {
            font-size: 2rem;
            line-height: 1;
        }
        .payment-title h2 {
            margin: 0;
            font-size: 1.75rem;
            color: var(--dark-text);
            font-weight: 700;
        }
        .payment-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0;
        }
        .info-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #8b5cf6;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-box strong {
            color: var(--dark-text);
        }
        .info-box span {
            font-weight: 700;
            color: var(--dark-text);
        }
        .btn-submit {
            background-color: #8b5cf6;
            color: white;
            width: 100%;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-submit:hover {
            background-color: #7c3aed;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #8b5cf6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="page-container">
    <a href="dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver al Dashboard
    </a>

    <div class="form-card">
        <div class="payment-header">
            <div class="payment-title">
                <span class="payment-icon">💰</span>
                <h2>Verificación de Pago</h2>
            </div>
            <p class="payment-subtitle">Ingresa la referencia para validar el pago</p>
        </div>
        
        <div class="info-box">
            <strong>Pedido:</strong>
            <span><?php echo htmlspecialchars($pedido['pedido']); ?></span>
        </div>
        
        <div class="form-group">
            <label for="referencia">Referencia de Pago</label>
            <input type="text" id="referencia" class="form-control" placeholder="Ingrese la referencia Ej: REF-123456" autocomplete="off">
        </div>
        
        <button class="btn-submit" id="btn-validate">Validar Pago</button>
    </div>
</div>

<div class="loading-overlay" id="loading">
    <div class="spinner"></div>
</div>

<script>
    document.getElementById('btn-validate').addEventListener('click', function() {
        const referencia = document.getElementById('referencia').value.trim();
        
        if (!referencia) {
            alert('Por favor ingrese la referencia de pago.');
            return;
        }
        
        const loading = document.getElementById('loading');
        loading.style.display = 'flex';
        
        fetch('process_payment_entregado.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                pedido_id: '<?php echo $pedido['id']; ?>',
                pedido_display: '<?php echo $pedido['pedido']; ?>',
                referencia: referencia
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('¡Referencia de pago guardada correctamente!');
                window.location.href = 'dashboard.php';
            } else {
                alert('Error: ' + (data.error || 'Error desconocido.'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión.');
        })
        .finally(() => {
            loading.style.display = 'none';
        });
    });
</script>

</body>
</html>
