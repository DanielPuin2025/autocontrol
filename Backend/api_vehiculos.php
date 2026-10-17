<?php
/**
 * API para gestión de vehículos (placas) en AutoControl
 * Endpoints:
 * GET    /api_vehiculos.php                    - Listar todos los vehículos
 * GET    /api_vehiculos.php?id=N               - Obtener un vehículo específico
 * GET    /api_vehiculos.php?cliente=N          - Obtener vehículos de un cliente
 * POST   /api_vehiculos.php                    - Crear nuevo vehículo
 * PUT    /api_vehiculos.php?id=N               - Actualizar vehículo
 * DELETE /api_vehiculos.php?id=N               - Eliminar vehículo
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
$clienteId = $_GET['cliente'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare(
                    'SELECT v.*, c.nombre, c.apellido
                     FROM vehiculos v
                     LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                     WHERE v.id_vehiculo = :id'
                );
                $stmt->execute([':id' => $id]);
                $vehiculo = $stmt->fetch();

                if (!$vehiculo) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Vehículo no encontrado']);
                    exit;
                }

                echo json_encode($vehiculo);
            } elseif ($clienteId) {
                $stmt = $pdo->prepare(
                    'SELECT v.*, c.nombre, c.apellido
                     FROM vehiculos v
                     LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                     WHERE v.id_cliente = :cliente
                     ORDER BY v.placa'
                );
                $stmt->execute([':cliente' => $clienteId]);
                $vehiculos = $stmt->fetchAll();
                echo json_encode($vehiculos);
            } else {
                $stmt = $pdo->query(
                    'SELECT v.*, c.nombre, c.apellido
                     FROM vehiculos v
                     LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
                     ORDER BY v.placa'
                );
                $vehiculos = $stmt->fetchAll();
                echo json_encode($vehiculos);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id_cliente']) || empty($data['placa'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de cliente y placa son requeridos']);
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO vehiculos (id_cliente, placa, marca, modelo, anio, tipo_vehiculo, kilometraje_actual)
                 VALUES (:id_cliente, :placa, :marca, :modelo, :anio, :tipo_vehiculo, :kilometraje_actual)'
            );

            $stmt->execute([
                ':id_cliente' => $data['id_cliente'],
                ':placa' => strtoupper($data['placa']),
                ':marca' => $data['marca'] ?? null,
                ':modelo' => $data['modelo'] ?? null,
                ':anio' => $data['anio'] ?? null,
                ':tipo_vehiculo' => $data['tipo_vehiculo'] ?? 'carro',
                ':kilometraje_actual' => $data['kilometraje_actual'] ?? 0
            ]);

            $nuevoId = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'ok' => true,
                'id' => $nuevoId,
                'mensaje' => 'Vehículo creado exitosamente'
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de vehículo requerido']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare(
                'UPDATE vehiculos
                 SET id_cliente = :id_cliente, placa = :placa, marca = :marca,
                     modelo = :modelo, anio = :anio, tipo_vehiculo = :tipo_vehiculo,
                     kilometraje_actual = :kilometraje_actual
                 WHERE id_vehiculo = :id'
            );

            $stmt->execute([
                ':id' => $id,
                ':id_cliente' => $data['id_cliente'],
                ':placa' => strtoupper($data['placa']),
                ':marca' => $data['marca'] ?? null,
                ':modelo' => $data['modelo'] ?? null,
                ':anio' => $data['anio'] ?? null,
                ':tipo_vehiculo' => $data['tipo_vehiculo'] ?? 'carro',
                ':kilometraje_actual' => $data['kilometraje_actual'] ?? 0
            ]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Vehículo actualizado exitosamente'
            ]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de vehículo requerido']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM vehiculos WHERE id_vehiculo = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Vehículo eliminado exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    error_log('Error en api_vehiculos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
