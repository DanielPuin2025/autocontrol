<?php
/**
 * Script de prueba de conexión y configuración de AutoControl
 * Ejecutar: http://localhost/autocontrol/test.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test AutoControl</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <h1>🔧 AutoControl - Test de Configuración</h1>";

// Test 1: Versión de PHP
echo "<div class='test-section'>
    <h2>1. Versión de PHP</h2>";
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.0.0', '>=')) {
    echo "<p class='success'>✓ PHP $phpVersion (Compatible)</p>";
} else {
    echo "<p class='warning'>⚠ PHP $phpVersion (Se recomienda PHP 8.0 o superior)</p>";
}
echo "</div>";

// Test 2: Extensiones requeridas
echo "<div class='test-section'>
    <h2>2. Extensiones de PHP</h2>
    <table>
        <tr><th>Extensión</th><th>Estado</th></tr>";

$extensions = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'json'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<tr>
        <td>$ext</td>
        <td class='" . ($loaded ? 'success' : 'error') . "'>" .
        ($loaded ? '✓ Instalada' : '✗ No instalada') . "</td>
    </tr>";
}
echo "</table></div>";

// Test 3: Conexión a la base de datos
echo "<div class='test-section'>
    <h2>3. Conexión a Base de Datos</h2>";

require_once __DIR__ . '/Backend/db.php';

try {
    $stmt = $pdo->query('SELECT VERSION() as version');
    $mysqlVersion = $stmt->fetch()['version'];
    echo "<p class='success'>✓ Conexión exitosa a MySQL $mysqlVersion</p>";

    // Listar variables de entorno
    echo "<pre>";
    echo "Host: " . getenv('AUTOCTRL_DB_HOST') ?: 'localhost' . "\n";
    echo "Puerto: " . getenv('AUTOCTRL_DB_PORT') ?: '3306' . "\n";
    echo "Base de datos: " . getenv('AUTOCTRL_DB_NAME') ?: 'autocontrol_db' . "\n";
    echo "Usuario: " . getenv('AUTOCTRL_DB_USER') ?: 'root' . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: Tablas de la base de datos
echo "<div class='test-section'>
    <h2>4. Tablas de la Base de Datos</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        echo "<p class='success'>✓ " . count($tables) . " tablas encontradas</p>";
        echo "<table>
            <tr><th>Tabla</th><th>Registros</th></tr>";

        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "<tr><td>$table</td><td>$count</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No se encontraron tablas. Importar autocontrol_db.sql</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 5: APIs REST
echo "<div class='test-section'>
    <h2>5. APIs REST</h2>
    <table>
        <tr><th>API</th><th>Ruta</th><th>Estado</th></tr>";

$apis = [
    'Clientes' => 'Backend/api_clientes.php',
    'Vehículos' => 'Backend/api_vehiculos.php',
    'Servicios' => 'Backend/api_servicios.php',
    'Usuarios' => 'Backend/api_usuarios.php',
    'Mantenimientos' => 'Backend/api_mantenimientos.php',
    'Facturas' => 'Backend/api_facturas.php',
    'API Original' => 'Backend/api.php'
];

foreach ($apis as $name => $path) {
    $exists = file_exists(__DIR__ . '/' . $path);
    echo "<tr>
        <td>$name</td>
        <td>$path</td>
        <td class='" . ($exists ? 'success' : 'error') . "'>" .
        ($exists ? '✓ Disponible' : '✗ No encontrada') . "</td>
    </tr>";
}
echo "</table></div>";

// Test 6: Permisos de escritura
echo "<div class='test-section'>
    <h2>6. Permisos del Sistema</h2>";

$writableTest = is_writable(__DIR__);
echo "<p class='" . ($writableTest ? 'success' : 'warning') . "'>";
echo $writableTest ? '✓ Directorio escribible' : '⚠ Directorio no escribible (puede afectar logs)';
echo "</p>";

echo "</div>";

// Test 7: Datos de ejemplo
echo "<div class='test-section'>
    <h2>7. Datos de Ejemplo</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clientes");
    $clientesCount = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM vehiculos");
    $vehiculosCount = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM servicios");
    $serviciosCount = $stmt->fetch()['count'];

    echo "<ul>
        <li>Clientes: <strong>$clientesCount</strong></li>
        <li>Vehículos: <strong>$vehiculosCount</strong></li>
        <li>Servicios: <strong>$serviciosCount</strong></li>
    </ul>";

    if ($clientesCount == 0) {
        echo "<p class='warning'>⚠ No hay datos. Puedes importar datos de ejemplo:</p>
        <pre>mysql -u root -p autocontrol_db &lt; Base_de_datos/datos_ejemplo.sql</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 8: Enlaces rápidos
echo "<div class='test-section'>
    <h2>8. Enlaces Rápidos</h2>
    <ul>
        <li><a href='Frontend/'>🏠 Aplicación Frontend</a></li>
        <li><a href='Backend/api_clientes.php'>📋 API Clientes (GET)</a></li>
        <li><a href='Backend/api_servicios.php'>🔧 API Servicios (GET)</a></li>
        <li><a href='Backend/api_vehiculos.php'>🚗 API Vehículos (GET)</a></li>
        <li><a href='Backend/README_API.md'>📚 Documentación de APIs</a></li>
    </ul>
</div>";

echo "<div class='test-section'>
    <p><strong>Todo listo!</strong> Si todos los tests están en verde, tu instalación de AutoControl está funcionando correctamente.</p>
</div>";

echo "</body></html>";
