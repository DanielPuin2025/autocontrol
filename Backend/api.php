<?php
/**
 * API muy simple de "llave-valor" para AutoControl.
 *
 * GET  api.php?clave=autocontrol_data_v1        -> devuelve {"valor": "...json..."}
 * POST api.php  con body JSON {"clave":"...", "valor":"...json..."} -> guarda y devuelve {"ok": true}
 *
 * Pensado para correr en XAMPP (htdocs) junto al resto de la app.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $clave = $_GET['clave'] ?? '';
    if ($clave === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Falta el parámetro clave']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT valor FROM almacenamiento WHERE clave = :clave');
    $stmt->execute([':clave' => $clave]);
    $fila = $stmt->fetch();

    echo json_encode(['valor' => $fila ? $fila['valor'] : null]);
    exit;
}

if ($metodo === 'POST') {
    $cuerpo = json_decode(file_get_contents('php://input'), true);
    $clave  = $cuerpo['clave'] ?? '';
    $valor  = $cuerpo['valor'] ?? '';

    if ($clave === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Falta el campo clave']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO almacenamiento (clave, valor) VALUES (:clave, :valor)
         ON DUPLICATE KEY UPDATE valor = :valor2'
    );
    $stmt->execute([
        ':clave'  => $clave,
        ':valor'  => $valor,
        ':valor2' => $valor,
    ]);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
