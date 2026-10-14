<?php
/**
 * Conexión a la base de datos MySQL.
 *
 * Valores por defecto de XAMPP:
 *   host: localhost
 *   usuario: root
 *   password: '' (vacío)
 *
 * En producción, configura AUTOCTRL_DB_HOST, AUTOCTRL_DB_PORT,
 * AUTOCTRL_DB_NAME, AUTOCTRL_DB_USER y AUTOCTRL_DB_PASS.
 */

$DB_HOST = getenv('AUTOCTRL_DB_HOST') ?: 'localhost';
$DB_PORT = getenv('AUTOCTRL_DB_PORT') ?: '3306';
$DB_NAME = getenv('AUTOCTRL_DB_NAME') ?: 'autocontrol_db';
$DB_USER = getenv('AUTOCTRL_DB_USER') ?: 'root';
$DB_PASS = getenv('AUTOCTRL_DB_PASS') ?: '';

// Charset UTF-8 para que tildes y ñ se guarden bien
$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('Error de conexión a la base de datos AutoControl: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'No se pudo conectar a la base de datos. Verifica la configuración de AutoControl.'
    ]);
    exit;
}
