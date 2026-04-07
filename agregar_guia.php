<?php
// agregar_guia.php
session_start();
require_once 'config.php';

// 1. Verificación de seguridad
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_role_id = $_SESSION['user_role'] ?? 0;
// Only role 3 (Guide Creator) and role 1 (Admin) can access this page
if ($user_role_id != 3 && $user_role_id != 1) {
    header('Location: dashboard.php');
    exit;
}

// 2. Get pedido from URL
$pedido_numero = $_GET['pedido'] ?? '';

if (empty($pedido_numero)) {
    header('Location: dashboard.php');
    exit;
}

// 3. Fetch pedido data
try {
    $endpoint = '/Pedidos?pedido=eq.' . urlencode($pedido_numero);
    $pedidos = supabase_request($endpoint, 'GET');
    
    if (empty($pedidos) || !is_array($pedidos)) {
        throw new Exception('Pedido no encontrado');
    }
    
    $pedido = $pedidos[0];
} catch (Exception $e) {
    die("Error al cargar el pedido: " . $e->getMessage());
}

$user_name = $_SESSION['user_name'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Guía CCR - Pedido <?php echo htmlspecialchars($pedido['pedido']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 24px;
            transition: all 0.2s;
        }

        .back-link:hover {
            gap: 12px;
            color: var(--primary-hover);
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <div class="page-container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>

        <div class="form-card">
            <!-- Header with Icon and Title -->
            <div class="guia-header">
                <div class="guia-title">
                    <span class="guia-icon">✅</span>
                    <h2>Guía de CCR</h2>
                </div>
                <p class="guia-subtitle">Verifica y registra la guía de Correos de Costa Rica</p>
            </div>

            <!-- Order Info Box -->
            <div class="info-box">
                <strong>Pedido:</strong> <span><?php echo htmlspecialchars($pedido['pedido']); ?></span>
            </div>

            <!-- Customer Information (Read-only) -->
            <div class="customer-info">
                <div class="info-row">
                    <strong>Nombre:</strong> <span><?php echo htmlspecialchars($pedido['nombre'] ?? '--'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Cédula:</strong> <span><?php echo htmlspecialchars($pedido['cedula'] ?? '--'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Correo Electrónico:</strong> <span><?php echo htmlspecialchars($pedido['correo_electronico'] ?? '--'); ?></span>
                </div>
            </div>

            <!-- Form -->
            <form id="guia-upload-form" enctype="multipart/form-data">
                <input type="hidden" name="pedido_id" value="<?php echo htmlspecialchars($pedido['id']); ?>">
                <input type="hidden" name="pedido_display" value="<?php echo htmlspecialchars($pedido['pedido']); ?>">
                
                <!-- Guide Number Input -->
                <div class="form-group">
                    <label for="numero-guia">Número de Guía</label>
                    <input type="text" 
                           id="numero-guia" 
                           name="numero_guia" 
                           placeholder="Ingresa el número de guía" 
                           required>
                </div>

                <!-- File Upload Area -->
                <div class="form-group">
                    <label>Comprobante de Calidad</label>
                    <div class="file-upload-area" id="file-upload-area">
                        <input type="file" 
                               id="file-input" 
                               name="file" 
                               accept="image/*,application/pdf" 
                               hidden 
                               required>
                        <div class="upload-placeholder" id="upload-placeholder">
                            <div class="camera-icon">📷</div>
                            <p class="upload-text">Toca para tomar foto o seleccionar imagen</p>
                            <p class="upload-subtext">Se convertirá automáticamente a WebP</p>
                        </div>
                        <div class="preview-container" id="preview-container" style="display: none;">
                            <img id="preview-image" src="" alt="Vista previa">
                            <button type="button" class="remove-preview" id="remove-preview">&times;</button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn-gradient" id="submit-btn">
                    Cargar Comprobante
                </button>

                <!-- Loading/Error Messages -->
                <div id="upload-message" class="upload-message" style="display: none;"></div>
            </form>
        </div>
    </div>

    <script>
        const fileUploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('file-input');
        const uploadPlaceholder = document.getElementById('upload-placeholder');
        const previewContainer = document.getElementById('preview-container');
        const previewImage = document.getElementById('preview-image');
        const removePreviewBtn = document.getElementById('remove-preview');
        const guiaForm = document.getElementById('guia-upload-form');
        const uploadMessage = document.getElementById('upload-message');
        const submitBtn = document.getElementById('submit-btn');

        // Click to upload
        fileUploadArea.addEventListener('click', function(e) {
            if (!e.target.closest('.remove-preview')) {
                fileInput.click();
            }
        });

        // File selected
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Show preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        uploadPlaceholder.style.display = 'none';
                        previewContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // For PDFs, just show that a file is selected
                    uploadPlaceholder.innerHTML = '<div class="camera-icon">📄</div><p class="upload-text">PDF seleccionado: ' + file.name + '</p>';
                }
            }
        });

        // Remove preview
        removePreviewBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fileInput.value = '';
            uploadPlaceholder.style.display = 'block';
            previewContainer.style.display = 'none';
            uploadPlaceholder.innerHTML = '<div class="camera-icon">📷</div><p class="upload-text">Toca para tomar foto o seleccionar imagen</p><p class="upload-subtext">Se convertirá automáticamente a WebP</p>';
        });

        // Form submission
        guiaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(guiaForm);
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
            uploadMessage.style.display = 'none';

            fetch('upload_guia.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    uploadMessage.className = 'upload-message success';
                    uploadMessage.textContent = data.message || '✓ Guía registrada exitosamente';
                    uploadMessage.style.display = 'block';
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    uploadMessage.className = 'upload-message error';
                    uploadMessage.textContent = '✗ ' + (data.error || 'Error al cargar la guía');
                    uploadMessage.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Cargar Comprobante';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                uploadMessage.className = 'upload-message error';
                uploadMessage.textContent = '✗ Error de conexión. Intenta nuevamente.';
                uploadMessage.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Cargar Comprobante';
            });
        });
    </script>
</body>
</html>
