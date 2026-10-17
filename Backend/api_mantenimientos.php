<?php
/**
 * API para gestión de mantenimientos en AutoControl
 * Endpoints:
 * GET    /api_mantenimientos.php                    - Listar todos los mantenimientos
 * GET    /api_mantenimientos.php?id=N               - Obtener un mantenimiento específico
 * GET    /api_mantenimientos.php?vehiculo=N         - Obtener mantenimientos de un vehículo
 * GET    /api_mantenimientos.php?cliente=N          - Obtener mantenimientos de un cliente
 * POST   /api_mantenimientos.php                    - Crear nuevo mantenimiento
 * PUT    /api_mantenimientos.php?id=N               - Actualizar mantenimiento
 * DELETE /api_mantenimientos.php?id=N               - Eliminar mantenimiento
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
$vehiculoId = $_GET['vehiculo'] ?? null;
$clienteId = $_GET['cliente'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare(
                    'SELECT m.*, c.nombre, c.apellido, c.telefono, c.whatsapp,
                            v.placa, v.marca, v.modelo,
                            s.nombre_servicio, s.precio
                     FROM mantenimientos m
                     LEFT JOIN clientes c ON m.id_cliente = c.id_cliente
                     LEFT JOIN vehiculos v ON m.id_vehiculo = v.id_vehiculo
                     LEFT JOIN servicios s ON m.id_servicio = s.id_servicio
                     WHERE m.id_mantenimiento = :id'
                );
                $stmt->execute([':id' => $id]);
                $mantenimiento = $stmt->fetch();

                if (!$mantenimiento) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Mantenimiento no encontrado']);
                    exit;
                }

                echo json_encode($mantenimiento);
            } elseif ($vehiculoId) {
                $stmt = $pdo->prepare(
                    'SELECT m.*, c.nombre, c.apellido, v.placa, s.nombre_servicio
                     FROM mantenimientos m
                     LEFT JOIN clientes c ON m.id_cliente = c.id_cliente
                     LEFT JOIN vehiculos v ON m.id_vehiculo = v.id_vehiculo
                     LEFT JOIN servicios s ON m.id_servicio = s.id_servicio
                     WHERE m.id_vehiculo = :vehiculo
                     ORDER BY m.fecha_servicio DESC'
                );
                $stmt->execute([':vehiculo' => $vehiculoId]);
                $mantenimientos = $stmt->fetchAll();
                echo json_encode($mantenimientos);
            } elseif ($clienteId) {
                $stmt = $pdo->prepare(
                    'SELECT m.*, c.nombre, c.apellido, v.placa, s.nombre_servicio
                     FROM mantenimientos m
                     LEFT JOIN clientes c ON m.id_cliente = c.id_cliente
                     LEFT JOIN vehiculos v ON m.id_vehiculo = v.id_vehiculo
                     LEFT JOIN servicios s ON m.id_servicio = s.id_servicio
                     WHERE m.id_cliente = :cliente
                     ORDER BY m.fecha_servicio DESC'
                );
                $stmt->execute([':cliente' => $clienteId]);
                $mantenimientos = $stmt->fetchAll();
                echo json_encode($mantenimientos);
            } else {
                $stmt = $pdo->query(
                    'SELECT m.*, c.nombre, c.apellido, v.placa, s.nombre_servicio
                     FROM mantenimientos m
                     LEFT JOIN clientes c ON m.id_cliente = c.id_cliente
                     LEFT JOIN vehiculos v ON m.id_vehiculo = v.id_vehiculo
                     LEFT JOIN servicios s ON m.id_servicio = s.id_servicio
                     ORDER BY m.fecha_servicio DESC
                     LIMIT 100'
                );
                $mantenimientos = $stmt->fetchAll();
                echo json_encode($mantenimientos);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id_cliente']) || empty($data['id_vehiculo']) || empty($data['id_servicio'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Cliente, vehículo y servicio son requeridos']);
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO mantenimientos
                 (id_cliente, id_vehiculo, id_servicio, fecha_servicio, proximo_mantenimiento,
                  kilometraje_actual, kilometraje_proximo, estado)
                 VALUES (:id_cliente, :id_vehiculo, :id_servicio, :fecha_servicio, :proximo_mantenimiento,
                         :kilometraje_actual, :kilometraje_proximo, :estado)'
            );

            $stmt->execute([
                ':id_cliente' => $data['id_cliente'],
                ':id_vehiculo' => $data['id_vehiculo'],
                ':id_servicio' => $data['id_servicio'],
                ':fecha_servicio' => $data['fecha_servicio'] ?? date('Y-m-d'),
                ':proximo_mantenimiento' => $data['proximo_mantenimiento'] ?? null,
                ':kilometraje_actual' => $data['kilometraje_actual'] ?? null,
                ':kilometraje_proximo' => $data['kilometraje_proximo'] ?? null,
                ':estado' => $data['estado'] ?? 'realizado'
            ]);

            $nuevoId = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'ok' => true,
                'id' => $nuevoId,
                'mensaje' => 'Mantenimiento creado exitosamente'
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de mantenimiento requerido']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare(
                'UPDATE mantenimientos
                 SET id_cliente = :id_cliente, id_vehiculo = :id_vehiculo, id_servicio = :id_servicio,
                     fecha_servicio = :fecha_servicio, proximo_mantenimiento = :proximo_mantenimiento,
                     kilometraje_actual = :kilometraje_actual, kilometraje_proximo = :kilometraje_proximo,
                     estado = :estado
                 WHERE id_mantenimiento = :id'
            );

            $stmt->execute([
                ':id' => $id,
                ':id_cliente' => $data['id_cliente'],
                ':id_vehiculo' => $data['id_vehiculo'],
                ':id_servicio' => $data['id_servicio'],
                ':fecha_servicio' => $data['fecha_servicio'],
                ':proximo_mantenimiento' => $data['proximo_mantenimiento'] ?? null,
                ':kilometraje_actual' => $data['kilometraje_actual'] ?? null,
                ':kilometraje_proximo' => $data['kilometraje_proximo'] ?? null,
                ':estado' => $data['estado'] ?? 'realizado'
            ]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Mantenimiento actualizado exitosamente'
            ]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de mantenimiento requerido']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM mantenimientos WHERE id_mantenimiento = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Mantenimiento eliminado exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    error_log('Error en api_mantenimientos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
