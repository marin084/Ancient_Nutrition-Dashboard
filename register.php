<?php
// register.php
session_start();
require_once 'config.php';

$error = '';
$success = '';

// Obtener roles activos para el select
$roles = [];
try {
    $rolesData = supabase_request('/roles?activo=is.true&select=*', 'GET');
    if (is_array($rolesData)) {
        $roles = $rolesData;
    }
} catch (Exception $e) {
    // Silencio si falla
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    // Se recibe solo el usuario y se concatena el dominio
    $usuario = trim($_POST['usuario'] ?? '');
    $email = $usuario . '@enlace.org';
    
    $password = $_POST['password'] ?? '';
    $id_rol = $_POST['id_rol'] ?? '';

    if (empty($nombre) || empty($apellidos) || empty($usuario) || empty($password) || empty($id_rol)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            // 1. Verificar si el correo ya existe
            $checkEndpoint = '/usuarios?correo=eq.' . urlencode($email) . '&select=id';
            $existingUser = supabase_request($checkEndpoint, 'GET');

            if (!empty($existingUser)) {
                $error = 'El usuario ya está registrado.';
            } else {
                // 2. Crear usuario con hash seguro
                $newUser = [
                    'nombre' => $nombre,
                    'apellidos' => $apellidos,
                    'correo' => $email, // Se guarda el correo completo
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id_rol' => (int)$id_rol
                ];

                supabase_request('/usuarios', 'POST', $newUser);
                $success = 'Usuario registrado exitosamente. <a href="login.php">Iniciar Sesión</a>';
                
                // Limpiar campos
                $nombre = ''; $apellidos = ''; $email = ''; $usuario = '';
            }
        } catch (Exception $e) {
            $error = 'Error al registrar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Gestión de Pedidos</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .register-link {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Registrar Nuevo Usuario</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="color: #0f5132; background-color: #d1e7dd; border-color: #badbcc; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <?php echo $success; ?>
            </div>
        <?php else: ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required value="<?php echo htmlspecialchars($nombre ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="apellidos">Apellidos</label>
                <input type="text" id="apellidos" name="apellidos" class="form-control" required value="<?php echo htmlspecialchars($apellidos ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="usuario">Usuario</label>
            <div class="input-group">
                <input type="text" id="usuario" name="usuario" class="form-control" required placeholder="usuario">
                <span class="input-group-text">@enlace.org</span>
            </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="id_rol">Rol</label>
                <select id="id_rol" name="id_rol" class="form-control" required>
                    <option value="">Seleccione un rol...</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo htmlspecialchars($rol['id']); ?>" <?php echo (isset($id_rol) && $id_rol == $rol['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rol['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Registrar Usuario</button>
            
            <div class="register-link">
                <a href="login.php">¿Ya tienes cuenta? Iniciar Sesión</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
