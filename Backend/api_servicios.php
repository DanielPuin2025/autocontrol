<?php
/**
 * API para gestión de servicios en AutoControl
 * Endpoints:
 * GET    /api_servicios.php              - Listar todos los servicios
 * GET    /api_servicios.php?id=N         - Obtener un servicio específico
 * POST   /api_servicios.php              - Crear nuevo servicio
 * PUT    /api_servicios.php?id=N         - Actualizar servicio
 * DELETE /api_servicios.php?id=N         - Eliminar servicio
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
                $stmt = $pdo->prepare('SELECT * FROM servicios WHERE id_servicio = :id');
                $stmt->execute([':id' => $id]);
                $servicio = $stmt->fetch();

                if (!$servicio) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Servicio no encontrado']);
                    exit;
                }

                echo json_encode($servicio);
            } else {
                $stmt = $pdo->query('SELECT * FROM servicios ORDER BY nombre_servicio');
                $servicios = $stmt->fetchAll();
                echo json_encode($servicios);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nombre_servicio'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nombre del servicio es requerido']);
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO servicios (nombre_servicio, descripcion, precio, duracion_km, duracion_dias, estado)
                 VALUES (:nombre_servicio, :descripcion, :precio, :duracion_km, :duracion_dias, :estado)'
            );

            $stmt->execute([
                ':nombre_servicio' => $data['nombre_servicio'],
                ':descripcion' => $data['descripcion'] ?? null,
                ':precio' => $data['precio'] ?? 0,
                ':duracion_km' => $data['duracion_km'] ?? null,
                ':duracion_dias' => $data['duracion_dias'] ?? null,
                ':estado' => $data['estado'] ?? 'activo'
            ]);

            $nuevoId = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'ok' => true,
                'id' => $nuevoId,
                'mensaje' => 'Servicio creado exitosamente'
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de servicio requerido']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare(
                'UPDATE servicios
                 SET nombre_servicio = :nombre_servicio, descripcion = :descripcion,
                     precio = :precio, duracion_km = :duracion_km, duracion_dias = :duracion_dias,
                     estado = :estado
                 WHERE id_servicio = :id'
            );

            $stmt->execute([
                ':id' => $id,
                ':nombre_servicio' => $data['nombre_servicio'],
                ':descripcion' => $data['descripcion'] ?? null,
                ':precio' => $data['precio'] ?? 0,
                ':duracion_km' => $data['duracion_km'] ?? null,
                ':duracion_dias' => $data['duracion_dias'] ?? null,
                ':estado' => $data['estado'] ?? 'activo'
            ]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Servicio actualizado exitosamente'
            ]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de servicio requerido']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM servicios WHERE id_servicio = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Servicio eliminado exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    error_log('Error en api_servicios.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
