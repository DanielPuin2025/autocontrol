<?php
/**
 * API para gestión de facturas en AutoControl
 * Endpoints:
 * GET    /api_facturas.php                    - Listar todas las facturas
 * GET    /api_facturas.php?id=N               - Obtener una factura específica con detalles
 * GET    /api_facturas.php?cliente=N          - Obtener facturas de un cliente
 * POST   /api_facturas.php                    - Crear nueva factura con detalles
 * PUT    /api_facturas.php?id=N               - Actualizar factura
 * DELETE /api_facturas.php?id=N               - Eliminar factura
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
                // Obtener factura con detalles
                $stmt = $pdo->prepare(
                    'SELECT f.*, c.nombre, c.apellido, c.documento, c.telefono,
                            v.placa, v.marca, v.modelo
                     FROM facturas f
                     LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
                     LEFT JOIN vehiculos v ON f.id_vehiculo = v.id_vehiculo
                     WHERE f.id_factura = :id'
                );
                $stmt->execute([':id' => $id]);
                $factura = $stmt->fetch();

                if (!$factura) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Factura no encontrada']);
                    exit;
                }

                // Obtener detalles de la factura
                $stmt = $pdo->prepare(
                    'SELECT df.*, s.nombre_servicio
                     FROM detalle_factura df
                     LEFT JOIN servicios s ON df.id_servicio = s.id_servicio
                     WHERE df.id_factura = :id'
                );
                $stmt->execute([':id' => $id]);
                $factura['detalles'] = $stmt->fetchAll();

                echo json_encode($factura);
            } elseif ($clienteId) {
                $stmt = $pdo->prepare(
                    'SELECT f.*, c.nombre, c.apellido, v.placa
                     FROM facturas f
                     LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
                     LEFT JOIN vehiculos v ON f.id_vehiculo = v.id_vehiculo
                     WHERE f.id_cliente = :cliente
                     ORDER BY f.fecha DESC'
                );
                $stmt->execute([':cliente' => $clienteId]);
                $facturas = $stmt->fetchAll();
                echo json_encode($facturas);
            } else {
                $stmt = $pdo->query(
                    'SELECT f.*, c.nombre, c.apellido, v.placa
                     FROM facturas f
                     LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
                     LEFT JOIN vehiculos v ON f.id_vehiculo = v.id_vehiculo
                     ORDER BY f.fecha DESC
                     LIMIT 100'
                );
                $facturas = $stmt->fetchAll();
                echo json_encode($facturas);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id_cliente']) || empty($data['id_vehiculo']) || empty($data['detalles'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Cliente, vehículo y detalles son requeridos']);
                exit;
            }

            $pdo->beginTransaction();

            try {
                // Crear factura
                $stmt = $pdo->prepare(
                    'INSERT INTO facturas
                     (id_cliente, id_vehiculo, fecha, kilometraje_actual, kilometraje_proximo,
                      subtotal, total, estado)
                     VALUES (:id_cliente, :id_vehiculo, :fecha, :kilometraje_actual, :kilometraje_proximo,
                             :subtotal, :total, :estado)'
                );

                $subtotal = 0;
                foreach ($data['detalles'] as $detalle) {
                    $subtotal += ($detalle['cantidad'] ?? 1) * ($detalle['precio'] ?? 0);
                }

                $stmt->execute([
                    ':id_cliente' => $data['id_cliente'],
                    ':id_vehiculo' => $data['id_vehiculo'],
                    ':fecha' => $data['fecha'] ?? date('Y-m-d H:i:s'),
                    ':kilometraje_actual' => $data['kilometraje_actual'] ?? null,
                    ':kilometraje_proximo' => $data['kilometraje_proximo'] ?? null,
                    ':subtotal' => $subtotal,
                    ':total' => $data['total'] ?? $subtotal,
                    ':estado' => $data['estado'] ?? 'pendiente'
                ]);

                $facturaId = $pdo->lastInsertId();

                // Crear detalles
                $stmtDetalle = $pdo->prepare(
                    'INSERT INTO detalle_factura (id_factura, id_servicio, cantidad, precio, subtotal)
                     VALUES (:id_factura, :id_servicio, :cantidad, :precio, :subtotal)'
                );

                foreach ($data['detalles'] as $detalle) {
                    $cantidad = $detalle['cantidad'] ?? 1;
                    $precio = $detalle['precio'] ?? 0;
                    $subtotalDetalle = $cantidad * $precio;

                    $stmtDetalle->execute([
                        ':id_factura' => $facturaId,
                        ':id_servicio' => $detalle['id_servicio'],
                        ':cantidad' => $cantidad,
                        ':precio' => $precio,
                        ':subtotal' => $subtotalDetalle
                    ]);
                }

                $pdo->commit();

                http_response_code(201);
                echo json_encode([
                    'ok' => true,
                    'id' => $facturaId,
                    'mensaje' => 'Factura creada exitosamente'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de factura requerido']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare(
                'UPDATE facturas
                 SET id_cliente = :id_cliente, id_vehiculo = :id_vehiculo, fecha = :fecha,
                     kilometraje_actual = :kilometraje_actual, kilometraje_proximo = :kilometraje_proximo,
                     subtotal = :subtotal, total = :total, estado = :estado
                 WHERE id_factura = :id'
            );

            $stmt->execute([
                ':id' => $id,
                ':id_cliente' => $data['id_cliente'],
                ':id_vehiculo' => $data['id_vehiculo'],
                ':fecha' => $data['fecha'],
                ':kilometraje_actual' => $data['kilometraje_actual'] ?? null,
                ':kilometraje_proximo' => $data['kilometraje_proximo'] ?? null,
                ':subtotal' => $data['subtotal'] ?? 0,
                ':total' => $data['total'] ?? 0,
                ':estado' => $data['estado'] ?? 'pendiente'
            ]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Factura actualizada exitosamente'
            ]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de factura requerido']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM facturas WHERE id_factura = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Factura eliminada exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    error_log('Error en api_facturas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
