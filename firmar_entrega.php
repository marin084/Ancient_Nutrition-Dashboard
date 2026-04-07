<?php
// firmar_entrega.php
session_start();
require_once 'config.php';

// 1. Verificación de seguridad
if (!isset($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI']);
    header('Location: login.php?redirect=' . $redirect);
    exit;
}

$user_role_id = $_SESSION['user_role'] ?? 0;
// Permitir solo a ciertos roles, principalmente 4 (Entrega), y quizas Admin(1)
if ($user_role_id != 4 && $user_role_id != 1) {
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
} catch (Exception $e) {
    die("Error al cargar pedido: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Firmar Entrega - Pedido <?php echo htmlspecialchars($pedido['pedido']); ?></title>
    <link rel="stylesheet" href="css/style.css?v=2.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }
        .info-label {
            font-weight: 600;
            color: var(--dark-text);
            font-size: 0.9rem;
        }
        .info-value {
            color: var(--text-muted);
            text-align: right;
            font-size: 0.95rem;
        }
        .info-value.highlight {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.1rem;
        }
        .info-value a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .info-value a:hover {
            text-decoration: underline;
        }
        .products-box {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border-left: 3px solid #28a745;
        }
        .products-title {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .products-list {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .signature-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-top: 24px;
        }
        .signature-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--dark-text);
            font-size: 1.25rem;
            font-weight: 700;
        }
        .signature-section-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .signature-pad-container {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            position: relative;
            background: #ffffff;
            background-image: 
                linear-gradient(#f1f5f9 1px, transparent 1px),
                linear-gradient(90deg, #f1f5f9 1px, transparent 1px);
            background-size: 20px 20px;
            height: 280px;
            width: 100%;
            overflow: hidden;
            touch-action: none;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
        }
        .signature-pad-container::before {
            content: '✍️ Firme aquí';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #cbd5e1;
            font-size: 1.5rem;
            font-weight: 300;
            pointer-events: none;
            z-index: 0;
        }
        .signature-pad-container.has-signature::before {
            display: none;
        }
        canvas {
            width: 100%!important;
            height: 100%!important;
            display: block;
            position: relative;
            z-index: 1;
        }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-clear {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(239,68,68,0.3);
        }
        .btn-clear:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(239,68,68,0.4);
        }
        .btn-confirm {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            flex-grow: 1;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(16,185,129,0.3);
        }
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16,185,129,0.4);
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            flex-direction: column;
        }
        .spinner {
            border: 4px solid #e5e7eb;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 640px) {
            .signature-pad-container {
                height: 220px;
            }
            .actions {
                flex-direction: column;
            }
            .btn-clear {
                order: 2;
            }
            .btn-confirm {
                order: 1;
            }
        }
    </style>
</head>
<body>

<div class="page-container">
    <a href="dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver al Dashboard
    </a>

    <div class="form-card">
        <div class="signature-header" style="text-align: center; margin-bottom: 24px;">
            <div class="signature-title" style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 8px;">
                <span class="signature-icon">📦</span>
                <h2>Pedido #<?php echo htmlspecialchars($pedido['pedido']); ?></h2>
            </div>
            <p class="signature-subtitle" style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Firma de recibido del cliente</p>
        </div>
        
        <!-- Information Grid -->
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Cliente</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['nombre']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Dirección</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['direccion'] ?? '--'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['correo_electronico']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total</span>
                <span class="info-value highlight">
                    ₡<?php echo number_format($pedido['total'] ?? 0, 2); ?>
                </span>
            </div>
        </div>

        <!-- Products Box -->
        <div class="products-box">
            <div class="products-title"><i class="fas fa-box"></i> Productos</div>
            <div class="products-list">
                <?php echo str_replace([',', '-'], '<br>', htmlspecialchars($pedido['productos'] ?? '--')); ?>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-section-title">
                <i class="fas fa-file-signature"></i> Firma de Recibido
            </div>
            <p class="signature-section-subtitle">
                Por favor, firme en el recuadro de abajo para confirmar la entrega.
            </p>
            
            <div class="signature-pad-container" id="signature-pad">
                <canvas id="canvas"></canvas>
            </div>

            <div class="actions">
                <button class="btn-clear" id="clear-btn">
                    <i class="fas fa-eraser"></i> Borrar
                </button>
                <button class="btn-confirm" id="save-btn">
                    <i class="fas fa-check"></i> Confirmar Entrega
                </button>
            </div>
        </div>
    </div>
</div>

<div class="loading-overlay" id="loading">
    <div class="spinner"></div>
    <p style="margin-top: 15px; font-weight: 600; color: var(--dark-text);">Procesando entrega...</p>
</div>

<script>
    // Signature Pad Logic
    const canvas = document.getElementById('canvas');
    const signaturePad = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    let isDrawing = false;
    let hasSignature = false;

    // Set line properties
    ctx.lineWidth = 2;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#000';

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = signaturePad.offsetWidth * ratio;
        canvas.height = signaturePad.offsetHeight * ratio;
        ctx.scale(ratio, ratio);
        
        // Reset properties after resize/scale
        ctx.lineWidth = 2;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#000';
    }

    window.addEventListener('resize', resizeCanvas);
    // Delay slightly to ensure container has size
    setTimeout(resizeCanvas, 100);

    function getTouchPos(canvasDom, touchEvent) {
        var rect = canvasDom.getBoundingClientRect();
        return {
            x: touchEvent.touches[0].clientX - rect.left,
            y: touchEvent.touches[0].clientY - rect.top
        };
    }

    function getMousePos(canvasDom, mouseEvent) {
        var rect = canvasDom.getBoundingClientRect();
        return {
            x: mouseEvent.clientX - rect.left,
            y: mouseEvent.clientY - rect.top
        };
    }

    // Touch events
    canvas.addEventListener('touchstart', function (e) {
        e.preventDefault(); // Prevent scrolling
        const pos = getTouchPos(canvas, e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        isDrawing = true;
        hasSignature = true;
        signaturePad.classList.add('has-signature'); // Hide placeholder
    }, { passive: false });

    canvas.addEventListener('touchmove', function (e) {
        e.preventDefault();
        if (!isDrawing) return;
        const pos = getTouchPos(canvas, e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }, { passive: false });

    canvas.addEventListener('touchend', function (e) {
        isDrawing = false;
    });

    // Mouse events (fallback for desktop validation)
    canvas.addEventListener('mousedown', function (e) {
        const pos = getMousePos(canvas, e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        isDrawing = true;
        hasSignature = true;
        signaturePad.classList.add('has-signature'); // Hide placeholder
    });

    canvas.addEventListener('mousemove', function (e) {
        if (!isDrawing) return;
        const pos = getMousePos(canvas, e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    });

    canvas.addEventListener('mouseup', function () {
        isDrawing = false;
    });

    canvas.addEventListener('mouseout', function () {
        isDrawing = false;
    });

    // Actions
    document.getElementById('clear-btn').addEventListener('click', function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height); // Note: verify coords with scale
        // Simpler: element width/height might need to be used instead of scaled width
        // But clearRect uses logic coords if scaled. 
        // Force reset
        canvas.width = canvas.width; 
        resizeCanvas();
        hasSignature = false;
        signaturePad.classList.remove('has-signature'); // Show placeholder again
    });

    document.getElementById('save-btn').addEventListener('click', function() {
        if (!hasSignature) {
            alert('Por favor, firme antes de confirmar.');
            return;
        }


        const loading = document.getElementById('loading');
        loading.style.display = 'flex';

        const dataURL = canvas.toDataURL('image/png');
        
        // Enviar al servidor
        fetch('upload_signature.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pedido_id: '<?php echo $pedido['id']; ?>', // ID Interno
                pedido_display: '<?php echo $pedido['pedido']; ?>', // ID Visual
                image: dataURL
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to dashboard after successful save
                window.location.href = 'dashboard.php'; 
            } else {
                alert('Error: ' + (data.error || 'Error desconocido al guardar la firma.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al guardar la firma.');
        })
        .finally(() => {
            loading.style.display = 'none';
        });
    });

</script>

</body>
</html>
