<?php
// test_session.php
// Este script verifica si las sesiones de PHP están funcionando y persistiendo
// Útils para detectar problemas de caché en hostings como WP Engine

session_start();

if (!isset($_SESSION['counter'])) {
    $_SESSION['counter'] = 0;
    $msg = "Nueva sesión iniciada.";
} else {
    $_SESSION['counter']++;
    $msg = "Sesión existente detectada.";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test de Sesiones PHP</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .box { padding: 20px; border: 1px solid #ccc; border-radius: 5px; max-width: 400px; margin: 0 auto; text-align: center; }
        .count { font-size: 3em; font-weight: bold; color: #0d6efd; margin: 20px 0; }
        .reload { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Test de Persistencia de Sesión</h2>
        <p><?php echo $msg; ?></p>
        
        <div class="count"><?php echo $_SESSION['counter']; ?></div>
        
        <p>Recarga esta página varias veces (F5).</p>
        <p>Si el número <strong>NO aumenta</strong> (se queda en 0 o 1), las sesiones no están funcionando (probablemente caché de WP Engine).</p>
        
        <a href="test_session.php" class="reload">Recargar Página</a>
    </div>
</body>
</html>
