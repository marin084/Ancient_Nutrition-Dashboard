<?php
// login.php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    // Capture redirect from POST
    $redirect = $_POST['redirect'] ?? '';
    
    // Construir correo
    $email = $usuario . '@enlace.org';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            // 1. Buscar usuario por correo usando la API
            $endpoint = '/usuarios?correo=eq.' . urlencode($email) . '&select=*';
            $users = supabase_request($endpoint, 'GET');

            // La API devuelve un array de objetos. Si está vacío, no existe el usuario.
            if (empty($users)) {
                $error = 'Credenciales incorrectas.';
            } else {
                $user = $users[0]; // Tomamos el primer resultado

                // 2. Verificar contraseña hash
                if (isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                    // Login exitoso
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nombre'] . ' ' . ($user['apellidos'] ?? '');
                    $_SESSION['user_role'] = $user['id_rol'];
                    $_SESSION['user_email'] = $user['correo'];
                    
                    $_SESSION['user_email'] = $user['correo'];
                    
                    // Check for valid redirect
                    if (!empty($redirect)) {
                        header('Location: ' . $redirect);
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                } else {
                    $error = 'Credenciales incorrectas.';
                }
            }
        } catch (Exception $e) {
            log_app_error("Error Login", ['email' => $email, 'error' => $e->getMessage()]);
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Gestión de Pedidos</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="https://monteoracion.com/logo-192.png">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="stylesheet" href="css/style.css?v=2.1">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f3f4f6;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .login-title {
            font-size: 26px;
            color: #1f2937;
            margin-bottom: 30px;
            font-weight: 800;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4b5563;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .input-group {
            display: flex;
            align-items: center;
        }
        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group-text {
            padding: 12px 16px;
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-left: none;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            color: #6b7280;
            font-size: 15px;
            white-space: nowrap;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .alert-danger {
            background-color: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
        }
        .login-container .btn {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        .login-container .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Iniciar Sesión</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <!-- Hidden redirect field -->
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? $_POST['redirect'] ?? ''); ?>">
            
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <div class="input-group">
                    <input type="text" id="usuario" name="usuario" class="form-control" required autofocus placeholder="usuario">
                    <span class="input-group-text">@enlace.org</span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Ingresar</button>
            <!-- <div style="text-align: center; margin-top: 15px;">
                <a href="register.php" style="color: #6c757d; text-decoration: none; font-size: 0.9em;">¿No tienes cuenta? Regístrate</a>
            </div> -->
        </form>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js')
                    .then(registration => {
                        console.log('SW registered:', registration.scope);
                    })
                    .catch(err => {
                        console.log('SW registration failed:', err);
                    });
            });
        }
    </script>
</body>
</html>
