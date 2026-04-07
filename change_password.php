<?php
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = 'Por favor complete todos los campos.';
    } elseif ($new_password !== $confirm_password) {
        $error_msg = 'Las nuevas contraseñas no coinciden.';
    } elseif (strlen($new_password) < 6) {
        $error_msg = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } else {
        // 1. Obtener usuario actual para verificar contraseña anterior
        $user_id = $_SESSION['user_id'];
        $endpoint = '/usuarios?id=eq.' . $user_id;

        try {
            $user_data = supabase_request($endpoint, 'GET');

            if (!empty($user_data) && is_array($user_data) && isset($user_data[0])) {
                $user = $user_data[0];

                if (password_verify($current_password, $user['password_hash'])) {
                    // 2. Hash nueva contraseña
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

                    // 3. Actualizar en Supabase
                    $update_data = ['password_hash' => $new_hash];
                    $update_endpoint = '/usuarios?id=eq.' . $user_id;
                    
                    // Supabase devuelve el registro actualizado con return=representation header (manejado en config o implícito si configurado)
                    // Para PATCH, verificar si funcionó.
                    $response = supabase_request($update_endpoint, 'PATCH', $update_data);

                    $success_msg = 'Contraseña actualizada correctamente.';
                    
                    // Opcional: Cerrar sesión o redirigir
                    // header('Location: dashboard.php');
                } else {
                    $error_msg = 'La contraseña actual es incorrecta.';
                }
            } else {
                $error_msg = 'Error al recuperar información del usuario.';
            }
        } catch (Exception $e) {
            $error_msg = 'Error de conexión: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Ancient Nutrition</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container" style="max-width: 500px; margin-top: 50px;">
        <h2 class="login-title">Cambiar Contraseña</h2>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert" style="background-color: var(--success-bg); color: var(--success-text);">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
            <p style="text-align: center; margin-top: 15px;">
                <a href="dashboard.php" class="btn btn-primary">Volver al Dashboard</a>
            </p>
        <?php else: ?>

        <form method="POST" action="change_password.php">
            <div class="form-group">
                <label for="current_password">Contraseña Actual</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">Nueva Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                </div>
                <small style="color: var(--text-muted);">Mínimo 6 caracteres.</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Nueva Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Actualizar Contraseña</button>
                <a href="dashboard.php" class="btn" style="background-color: var(--secondary-color); color: white; flex: 1;">Cancelar</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
