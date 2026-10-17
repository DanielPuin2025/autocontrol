<?php
/**
 * Script de verificación y corrección de la base de datos AutoControl
 * Ejecutar: http://localhost/autocontrol/verificar_db.php
 */

header('Content-Type: application/json; charset=utf-8');

// Cargar configuración
$DB_HOST = getenv('AUTOCTRL_DB_HOST') ?: 'localhost';
$DB_PORT = getenv('AUTOCTRL_DB_PORT') ?: '3306';
$DB_NAME = getenv('AUTOCTRL_DB_NAME') ?: 'autocontrol_db';
$DB_USER = getenv('AUTOCTRL_DB_USER') ?: 'root';
$DB_PASS = getenv('AUTOCTRL_DB_PASS') ?: '';

$resultado = [
    'ok' => false,
    'mensajes' => [],
    'errores' => []
];

try {
    // 1. Intentar conexión
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $resultado['mensajes'][] = "✓ Conexión exitosa a la base de datos '$DB_NAME'";

    // 2. Verificar que existe la tabla almacenamiento
    $stmt = $pdo->query("SHOW TABLES LIKE 'almacenamiento'");
    $tablaExiste = $stmt->fetch();

    if (!$tablaExiste) {
        // Crear la tabla si no existe
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS almacenamiento (
                clave VARCHAR(100) NOT NULL PRIMARY KEY,
                valor LONGTEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $resultado['mensajes'][] = "✓ Tabla 'almacenamiento' creada exitosamente";
    } else {
        $resultado['mensajes'][] = "✓ Tabla 'almacenamiento' existe";
    }

    // 3. Verificar otras tablas importantes
    $tablasRequeridas = [
        'clientes', 'vehiculos', 'servicios', 'usuarios',
        'mantenimientos', 'facturas', 'detalle_factura',
        'administradores', 'configuracion', 'alertas'
    ];

    $tablasFaltantes = [];
    foreach ($tablasRequeridas as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if (!$stmt->fetch()) {
            $tablasFaltantes[] = $tabla;
        }
    }

    if (count($tablasFaltantes) > 0) {
        $resultado['errores'][] = "Faltan tablas: " . implode(', ', $tablasFaltantes);
        $resultado['mensajes'][] = "⚠ Importa el archivo Base_de_datos/autocontrol_db.sql";
    } else {
        $resultado['mensajes'][] = "✓ Todas las tablas requeridas existen";
    }

    // 4. Probar API de almacenamiento
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM almacenamiento");
    $count = $stmt->fetch()['count'];
    $resultado['mensajes'][] = "✓ Tabla almacenamiento tiene $count registro(s)";

    // 5. Insertar o actualizar un registro de prueba
    $stmt = $pdo->prepare(
        'INSERT INTO almacenamiento (clave, valor) VALUES (:clave, :valor)
         ON DUPLICATE KEY UPDATE valor = :valor2'
    );
    $stmt->execute([
        ':clave' => 'test_conexion',
        ':valor' => json_encode(['timestamp' => date('Y-m-d H:i:s'), 'status' => 'ok']),
        ':valor2' => json_encode(['timestamp' => date('Y-m-d H:i:s'), 'status' => 'ok'])
    ]);

    $resultado['mensajes'][] = "✓ Prueba de escritura exitosa en almacenamiento";

    // 6. Verificar datos de servicios
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM servicios");
    $serviciosCount = $stmt->fetch()['count'];

    if ($serviciosCount == 0) {
        $resultado['mensajes'][] = "⚠ No hay servicios registrados. Se recomienda importar datos_ejemplo.sql";
    } else {
        $resultado['mensajes'][] = "✓ Hay $serviciosCount servicio(s) registrado(s)";
    }

    $resultado['ok'] = count($resultado['errores']) == 0;

} catch (PDOException $e) {
    $resultado['errores'][] = "Error de conexión: " . $e->getMessage();
    $resultado['mensajes'][] = "Verifica las variables de entorno:";
    $resultado['mensajes'][] = "  AUTOCTRL_DB_HOST = $DB_HOST";
    $resultado['mensajes'][] = "  AUTOCTRL_DB_PORT = $DB_PORT";
    $resultado['mensajes'][] = "  AUTOCTRL_DB_NAME = $DB_NAME";
    $resultado['mensajes'][] = "  AUTOCTRL_DB_USER = $DB_USER";
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
