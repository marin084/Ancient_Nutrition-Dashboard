<?php
// test_roles.php
require_once 'config.php';

echo "<h1>Debugging Roles</h1>";

echo "<h2>1. Intento sin filtros</h2>";
try {
    $rolesAll = supabase_request('/roles?select=*', 'GET');
    echo "<pre>";
    print_r($rolesAll);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Intento con filtro (activo=true)</h2>";
try {
    // Probamos 'eq.true' que suele ser el estándar, o 'is.true'
    $rolesFilter = supabase_request('/roles?activo=is.true&select=*', 'GET');
    echo "<pre>";
    print_r($rolesFilter);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
