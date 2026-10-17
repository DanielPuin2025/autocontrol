<?php
/**
 * API para gestión de clientes en AutoControl
 * Endpoints:
 * GET    /api_clientes.php              - Listar todos los clientes
 * GET    /api_clientes.php?id=N         - Obtener un cliente específico
 * POST   /api_clientes.php              - Crear nuevo cliente
 * PUT    /api_clientes.php?id=N         - Actualizar cliente
 * DELETE /api_clientes.php?id=N         - Eliminar cliente
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id_cliente = :id');
                $stmt->execute([':id' => $id]);
                $cliente = $stmt->fetch();

                if (!$cliente) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Cliente no encontrado']);
                    exit;
                }

                echo json_encode($cliente);
            } else {
                $stmt = $pdo->query('SELECT * FROM clientes ORDER BY apellido, nombre');
                $clientes = $stmt->fetchAll();
                echo json_encode($clientes);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nombre']) || empty($data['apellido'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nombre y apellido son requeridos']);
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO clientes (nombre, apellido, documento, telefono, whatsapp, correo)
                 VALUES (:nombre, :apellido, :documento, :telefono, :whatsapp, :correo)'
            );

            $stmt->execute([
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':documento' => $data['documento'] ?? null,
                ':telefono' => $data['telefono'] ?? null,
                ':whatsapp' => $data['whatsapp'] ?? null,
                ':correo' => $data['correo'] ?? null
            ]);

            $nuevoId = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'ok' => true,
                'id' => $nuevoId,
                'mensaje' => 'Cliente creado exitosamente'
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de cliente requerido']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare(
                'UPDATE clientes
                 SET nombre = :nombre, apellido = :apellido, documento = :documento,
                     telefono = :telefono, whatsapp = :whatsapp, correo = :correo
                 WHERE id_cliente = :id'
            );

            $stmt->execute([
                ':id' => $id,
                ':nombre' => $data['nombre'],
                ':apellido' => $data['apellido'],
                ':documento' => $data['documento'] ?? null,
                ':telefono' => $data['telefono'] ?? null,
                ':whatsapp' => $data['whatsapp'] ?? null,
                ':correo' => $data['correo'] ?? null
            ]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Cliente actualizado exitosamente'
            ]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de cliente requerido']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM clientes WHERE id_cliente = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Cliente eliminado exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    error_log('Error en api_clientes.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
