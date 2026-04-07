<?php
// comprobante_entrega.php
session_start();
require_once 'config.php';

// 1. Verificación de seguridad y Deep Linking
if (!isset($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI']);
    header('Location: login.php?redirect=' . $redirect);
    exit;
}

$user_role_id = $_SESSION['user_role'] ?? 0;
// Permitir Rol 8 (Correos de Costa Rica / Mensajero), Rol 4 (Entrega) y Admin (1)
if ($user_role_id != 8 && $user_role_id != 4 && $user_role_id != 1) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante Entrega - Pedido <?php echo htmlspecialchars($pedido['pedido']); ?></title>
    <link rel="stylesheet" href="css/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .delivery-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .delivery-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .delivery-icon {
            font-size: 2rem;
            line-height: 1;
        }
        .delivery-title h2 {
            margin: 0;
            font-size: 1.75rem;
            color: var(--dark-text);
            font-weight: 700;
        }
        .delivery-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0;
        }
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
        .info-value a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .info-value a:hover {
            text-decoration: underline;
        }
        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 30px 20px;
            margin-bottom: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background-color: #f8fafc;
            text-align: center;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #6366f1;
            background-color: #eef2ff;
        }
        .upload-icon {
            font-size: 48px;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        .upload-text {
            color: #64748b;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .upload-subtext {
            color: #94a3b8;
            font-size: 12px;
        }
        #file-input {
            display: none;
        }
        #image-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            display: none;
            margin-top: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-submit {
            background-color: #6366f1;
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
            background-color: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }
        .btn-submit:disabled {
            background-color: #a5a6f6;
            cursor: not-allowed;
            transform: none;
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
            border-top: 4px solid #6366f1;
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
        <div class="delivery-header">
            <div class="delivery-title">
                <span class="delivery-icon">📋</span>
                <h2>Comprobante de Entrega</h2>
            </div>
            <p class="delivery-subtitle">Carga el comprobante de entrega del pedido</p>
        </div>
        
        <!-- Information Grid -->
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Pedido</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['pedido']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Cliente</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['nombre'] ?? '--'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Dirección</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['direccion'] ?? '--'); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['correo_electronico'] ?? '--'); ?></span>
            </div>
            <?php if (!empty($pedido['guia'])): ?>
            <div class="info-item">
                <span class="info-label">Guía</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['guia']); ?></span>
            </div>
            <?php if (!empty($pedido['qr_ccr'])): ?>
            <?php 
                $qr_url = $pedido['qr_ccr'];
                if (strpos($qr_url, 'http') !== 0) {
                    $qr_url = rtrim($supabaseUrl, '/') . '/storage/v1/object/public/' . ltrim($qr_url, '/');
                }
            ?>
            <div style="text-align: center; margin-top: -4px; margin-bottom: 8px;">
                <?php if (strpos(strtolower($pedido['qr_ccr']), '.pdf') !== false): ?>
                    <a href="<?php echo htmlspecialchars($qr_url); ?>" target="_blank" style="display: inline-block; padding: 8px 16px; background-color: #f8fafc; color: #6366f1; border: 1px solid #6366f1; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 0.9rem;">
                        <i class="fas fa-file-pdf"></i> Ver Guía PDF
                    </a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($qr_url); ?>" target="_blank">
                        <img src="<?php echo htmlspecialchars($qr_url); ?>" alt="Guía" style="max-width: 100%; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.05); max-height: 250px; object-fit: contain; background: #fff; padding: 4px;">
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </div>
        
        <div style="text-align: left; margin-bottom: 10px; font-weight: 600; color: #374151;">Comprobante de entrega</div>
        
        <div class="upload-area" id="upload-trigger">
            <i class="fas fa-camera upload-icon"></i>
            <div class="upload-text">Toca para tomar foto o seleccionar imagen</div>
            <div class="upload-subtext">Se convertirá automáticamente a WebP</div>
            <img id="image-preview" alt="Vista previa">
        </div>
        
        <input type="file" id="file-input" accept="image/*" capture="environment">
        
        <button class="btn-submit" id="btn-submit">Cargar Comprobante</button>
    </div>
</div>

<div class="loading-overlay" id="loading">
    <div class="spinner"></div>
</div>

<script>
    const uploadTrigger = document.getElementById('upload-trigger');
    const fileInput = document.getElementById('file-input');
    const imagePreview = document.getElementById('image-preview');
    const uploadIcon = document.querySelector('.upload-icon');
    const uploadText = document.querySelector('.upload-text');
    const uploadSubtext = document.querySelector('.upload-subtext');
    const btnSubmit = document.getElementById('btn-submit');
    const loading = document.getElementById('loading');
    
    // Trigger file input on click
    uploadTrigger.addEventListener('click', () => {
        fileInput.click();
    });
    
    // Handle file selection
    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Validate it is an image
            if (!file.type.startsWith('image/')) {
                alert('Por favor selecciona un archivo de imagen válido.');
                this.value = ''; // Reset
                return;
            }

            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                
                // Hide placeholder elements
                uploadIcon.style.display = 'none';
                uploadText.style.display = 'none';
                uploadSubtext.style.display = 'none';
            }
            
            reader.readAsDataURL(file);
        }
    });
    
    // Handle submit
    btnSubmit.addEventListener('click', function() {
        if (!fileInput.files || !fileInput.files[0]) {
            alert('Por favor selecciona o toma una foto del comprobante.');
            return;
        }
        
        loading.style.display = 'flex';
        
        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append('file', file);
        formData.append('pedido_id', '<?php echo $pedido['id']; ?>');
        formData.append('pedido_display', '<?php echo $pedido['pedido']; ?>');
        formData.append('tipo_doc', 'comprobante_entrega'); // Identificador para el backend
        
        // Usaremos el mismo upload handler si es posible, o adaptamos uno
        // Nota: El usuario mencionó "hagamos la visual y luego ajustamos la lógica".
        // Por ahora, asumiremos que existe un endpoint o simularemos el éxito
        // para cumplir con "visual primero".
        
        // Simulación de carga exitosa para demostración visual (o conectar a upload real si existe)
        // Revisando archivos, existe upload_signature.php y upload-handler.php?
        // Vamos a intentar usar un endpoint genérico, pero si falla, mostraremos alert.
        
        // Para este paso, usaremos un endpoint que definiremos.
        // Si el usuario dijo "luego ajustamos la lógica", quizás solo quiere la UI.
        // Pero intentaré hacer el POST a 'upload_comprobante.php' (que crearé o imitaré).
        
        // VO: Voy a apuntar a 'upload_signature.php' como placeholder o 'upload_handler.php' si es generico.
        // Mejor creo un placeholder en JS para la demo visual si no hay backend listo.
        // Pero el usuario dijo "luego ajustamos la lógica", así que solo la UI es mandatoria.
        // Sin embargo, pondré el fetch a un archivo PHP que (aunque no exista o falle) demuestre la intención.
        
        fetch('upload_comprobante.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text.trim());
                if (data.success) {
                    alert('¡Comprobante cargado correctamente!');
                    try {
                        if (window.opener) {
                            window.opener.location.reload();
                        }
                        window.close();
                    } catch (closeErr) {
                        // Window close might fail, fallback to redirect
                    }
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Error del servidor: ' + (data.error || 'Error desconocido.'));
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.log('Raw Response:', text);
                alert('Error inesperado: ' + text.substring(0, 100) + '...');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión o red: ' + err.message);
        })
        .finally(() => {
            loading.style.display = 'none';
        });
    });
</script>

</body>
</html>
