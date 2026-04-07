<?php
// test_db.php
require_once 'config.php';

echo "<h1>Prueba de Conexión (API)</h1>";

try {
    // Intentar traer 1 pedido para verificar conexión
    $pedidos = supabase_request('/pedidos?select=*&limit=1', 'GET');
    
    echo "<h2 style='color: green;'>✅ Conexión Exitosa con la API de Supabase!</h2>";
    echo "<p>Se obtuvo respuesta correctamente.</p>";
    echo "<pre>";
    print_r($pedidos);
    echo "</pre>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error de Conexión</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifica tu <code>config.php</code> y asegúrate de haber puesto la URL y la ANON KEY correctamente.</p>";
}
?>
