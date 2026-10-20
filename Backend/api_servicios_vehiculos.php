<?php
/**
 * API para gestión de servicios por tipo de vehículo
 * GET /api_servicios_vehiculos.php - Listar todas las relaciones
 * GET /api_servicios_vehiculos.php?id_servicio=1 - Relaciones de un servicio
 * POST /api_servicios_vehiculos.php - Crear relación
 * PUT /api_servicios_vehiculos.php?id=1 - Actualizar relación
 * DELETE /api_servicios_vehiculos.php?id=1 - Eliminar relación
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
$idServicio = $_GET['id_servicio'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            if ($idServicio) {
                // Obtener relaciones de un servicio específico
                $stmt = $pdo->prepare(
                    'SELECT * FROM servicios_vehiculos
                     WHERE id_servicio = :id_servicio
                     ORDER BY tipo_vehiculo'
                );
                $stmt->execute([':id_servicio' => $idServicio]);
                $relaciones = $stmt->fetchAll();
                echo json_encode($relaciones);
            } else {
                // Obtener todas las relaciones
                $stmt = $pdo->query('SELECT * FROM servicios_vehiculos ORDER BY id_servicio, tipo_vehiculo');
                $relaciones = $stmt->fetchAll();
                echo json_encode($relaciones);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id_servicio']) || empty($data['tipo_vehiculo'])) {
                http_response_code(400);
                echo json_encode(['error' => 'id_servicio y tipo_vehiculo son requeridos']);
                exit;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO servicios_vehiculos
                 (id_servicio, tipo_vehiculo, precio_especifico, duracion_km_especifica, duracion_dias_especifica, notas, activo)
                 VALUES (:id_servicio, :tipo_vehiculo, :precio, :km, :dias, :notas, :activo)'
            );

            $stmt->execute([
                ':id_servicio' => $data['id_servicio'],
                ':tipo_vehiculo' => $data['tipo_vehiculo'],
                ':precio' => $data['precio_especifico'] ?? null,
                ':km' => $data['duracion_km_especifica'] ?? null,
                ':dias' => $data['duracion_dias_especifica'] ?? null,
                ':notas' => $data['notas'] ?? null,
                ':activo' => $data['activo'] ?? true
            ]);

            $nuevoId = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'ok' => true,
                'id' => $nuevoId,
                'mensaje' => 'Relación creada exitosamente'
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare(
                'UPDATE servicios_vehiculos
                 SET precio_especifico = :precio,
                     duracion_km_especifica = :km,
                     duracion_dias_especifica = :dias,
                     notas = :notas,
                     activo = :activo
                 WHERE id_servicio_vehiculo = :id'
            );

            $stmt->execute([
                ':id' => $id,
                ':precio' => $data['precio_especifico'] ?? null,
                ':km' => $data['duracion_km_especifica'] ?? null,
                ':dias' => $data['duracion_dias_especifica'] ?? null,
                ':notas' => $data['notas'] ?? null,
                ':activo' => $data['activo'] ?? true
            ]);

            echo json_encode(['ok' => true, 'mensaje' => 'Relación actualizada']);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM servicios_vehiculos WHERE id_servicio_vehiculo = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode(['ok' => true, 'mensaje' => 'Relación eliminada']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
