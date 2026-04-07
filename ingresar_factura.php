<?php
// ingresar_factura.php
session_start();
require_once 'config.php';

// 1. Verificación de seguridad
if (!isset($_SESSION['user_id'])) {
    $redirect = urlencode($_SERVER['REQUEST_URI']);
    header('Location: login.php?redirect=' . $redirect);
    exit;
}

$user_role_id = $_SESSION['user_role'] ?? 0;
// Permitir solo a Admin (Rol 1) y Rol 5 (Facturas)
if ($user_role_id != 1 && $user_role_id != 5) {
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
    <title>Ingresar Factura - Pedido <?php echo htmlspecialchars($pedido['pedido']); ?></title>
    <link rel="stylesheet" href="css/style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #8b5cf6;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .invoice-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .invoice-icon {
            font-size: 2rem;
            line-height: 1;
        }
        .invoice-title h2 {
            margin: 0;
            font-size: 1.75rem;
            color: var(--dark-text);
            font-weight: 700;
        }
        .invoice-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0;
        }
        .order-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 28px;
            border-left: 5px solid #8b5cf6;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }
        .info-row:not(:last-child) {
            border-bottom: 1px solid #d1d5db;
        }
        .order-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 14px;
        }
        .order-value {
            font-weight: 700;
            color: #1f2937;
            font-size: 16px;
            text-align: right;
        }
        .order-value.highlight {
            font-size: 20px;
            color: #8b5cf6;
        }
        .form-group {
            margin-bottom: 24px;
            text-align: left;
        }
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 14px;
            padding: 32px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background-color: #fafafa;
            margin-bottom: 12px;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #8b5cf6;
            background-color: #f5f3ff;
        }
        .upload-area.has-file {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        .upload-icon {
            font-size: 48px;
            color: #9ca3af;
            margin-bottom: 12px;
        }
        .upload-area.has-file .upload-icon {
            color: #10b981;
        }
        .upload-text {
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .upload-subtext {
            color: #9ca3af;
            font-size: 13px;
        }
        .file-name {
            display: none;
            color: #059669;
            font-weight: 600;
            font-size: 14px;
            margin-top: 8px;
            word-break: break-all;
        }
        .upload-area.has-file .file-name {
            display: block;
        }
        #file-input {
            display: none;
        }
        .btn-submit {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            width: 100%;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .btn-submit:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        /* Loading Overlay */
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
            border: 5px solid #f3f4f6;
            border-top: 5px solid #8b5cf6;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            margin-top: 20px;
            font-weight: 600;
            color: #4b5563;
            font-size: 16px;
        }

        /* Confirmation Modal */
        .confirm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        .confirm-modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .confirm-modal-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .confirm-modal-title {
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .confirm-modal-text {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .confirm-modal-buttons {
            display: flex;
            gap: 12px;
        }
        .confirm-modal-btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .confirm-modal-btn-cancel {
            background: #f3f4f6;
            color: #4b5563;
        }
        .confirm-modal-btn-cancel:hover {
            background: #e5e7eb;
        }
        .confirm-modal-btn-confirm {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        .confirm-modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
        }
    </style>
</head>
<body>

<div class="page-container" style="max-width: 600px; margin: 40px auto;">
    <a href="dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver al Dashboard
    </a>

    <div class="form-card">
        <div class="invoice-header">
            <div class="invoice-title">
                <span class="invoice-icon">📄</span>
                <h2>Número de Factura</h2>
            </div>
            <p class="invoice-subtitle">Registra el número de factura electrónica</p>
        </div>
        
        <div class="order-box">
            <div class="info-grid">
                <div class="info-row">
                    <span class="order-label">Pedido:</span>
                    <span class="order-value highlight"><?php echo htmlspecialchars($pedido['pedido']); ?></span>
                </div>
                <?php if (!empty($pedido['nombre'])): ?>
                <div class="info-row">
                    <span class="order-label">Nombre:</span>
                    <span class="order-value"><?php echo htmlspecialchars($pedido['nombre']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($pedido['cedula'])): ?>
                <div class="info-row">
                    <span class="order-label">Cédula:</span>
                    <span class="order-value"><?php echo htmlspecialchars($pedido['cedula']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($pedido['total'])): ?>
                <div class="info-row">
                    <span class="order-label">Total:</span>
                    <span class="order-value">₡<?php echo number_format($pedido['total'], 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="numero-factura">Número de Factura</label>
            <input type="text" 
                   id="numero-factura" 
                   class="form-input" 
                   placeholder="Ej: 1234567890"
                   maxlength="50">
        </div>

        <div class="form-group">
            <label class="form-label">Archivo PDF de Factura</label>
            <div class="upload-area" id="upload-trigger">
                <i class="fas fa-file-pdf upload-icon"></i>
                <div class="upload-text">Toca para seleccionar PDF</div>
                <div class="upload-subtext">Formato: PDF (máx. 10MB)</div>
                <div class="file-name" id="file-name"></div>
            </div>
        </div>
        
        <input type="file" id="file-input" accept="application/pdf,.pdf">
        
        <button class="btn-submit" id="btn-submit">Registrar Factura</button>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="confirm-modal" id="confirm-modal">
    <div class="confirm-modal-content">
        <div class="confirm-modal-icon">⚠️</div>
        <div class="confirm-modal-title">Confirmar Registro</div>
        <div class="confirm-modal-text">
            ¿Confirmas que deseas registrar esta factura?<br>
            Esta acción cambiará el estado del pedido a <strong>FINALIZADO</strong>.
        </div>
        <div class="confirm-modal-buttons">
            <button class="confirm-modal-btn confirm-modal-btn-cancel" id="modal-cancel">Cancelar</button>
            <button class="confirm-modal-btn confirm-modal-btn-confirm" id="modal-confirm">Sí, Registrar</button>
        </div>
    </div>
</div>

<div class="loading-overlay" id="loading">
    <div class="spinner"></div>
    <p class="loading-text">Subiendo factura...</p>
</div>

<script>
    const uploadTrigger = document.getElementById('upload-trigger');
    const fileInput = document.getElementById('file-input');
    const fileNameDisplay = document.getElementById('file-name');
    const numeroFacturaInput = document.getElementById('numero-factura');
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
            
            // Validate it is a PDF
            if (file.type !== 'application/pdf') {
                alert('Por favor selecciona un archivo PDF válido.');
                this.value = '';
                uploadTrigger.classList.remove('has-file');
                return;
            }

            // Validate file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                alert('El archivo es demasiado grande. Máximo 10MB.');
                this.value = '';
                uploadTrigger.classList.remove('has-file');
                return;
            }

            // Show file name
            fileNameDisplay.textContent = file.name;
            uploadTrigger.classList.add('has-file');
        }
    });
    
    // Handle submit
    const confirmModal = document.getElementById('confirm-modal');
    const modalCancel = document.getElementById('modal-cancel');
    const modalConfirm = document.getElementById('modal-confirm');

    btnSubmit.addEventListener('click', function() {
        const numeroFactura = numeroFacturaInput.value.trim();
        
        if (!numeroFactura) {
            alert('Por favor ingresa el número de factura.');
            numeroFacturaInput.focus();
            return;
        }

        if (!fileInput.files || !fileInput.files[0]) {
            alert('Por favor selecciona el archivo PDF de la factura.');
            return;
        }
        
        // Show custom confirmation modal
        confirmModal.style.display = 'flex';
    });

    // Cancel button
    modalCancel.addEventListener('click', function() {
        confirmModal.style.display = 'none';
    });

    // Confirm button
    modalConfirm.addEventListener('click', function() {
        confirmModal.style.display = 'none';
        loading.style.display = 'flex';
        
        const numeroFactura = numeroFacturaInput.value.trim();
        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append('file', file);
        formData.append('pedido_id', '<?php echo $pedido['id']; ?>');
        formData.append('pedido_display', '<?php echo $pedido['pedido']; ?>');
        formData.append('numero_factura', numeroFactura);
        
        fetch('upload_factura.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text.trim());
                if (data.success) {
                    // Redirect without showing alert
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

    // Drag and drop support
    uploadTrigger.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadTrigger.classList.add('dragover');
    });

    uploadTrigger.addEventListener('dragleave', () => {
        uploadTrigger.classList.remove('dragover');
    });

    uploadTrigger.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadTrigger.classList.remove('dragover');
        
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            fileInput.files = e.dataTransfer.files;
            const event = new Event('change');
            fileInput.dispatchEvent(event);
        }
    });
</script>

</body>
</html>
