<?php
/**
 * API para gestión de imágenes en AutoControl
 * Endpoints:
 * GET    /api_imagenes.php?tipo=logo_taller           - Obtener imagen por tipo
 * GET    /api_imagenes.php?tipo=foto_servicio&ref=123 - Obtener imagen de servicio
 * POST   /api_imagenes.php                            - Subir/actualizar imagen
 * DELETE /api_imagenes.php?tipo=X&ref=Y              - Eliminar imagen
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$tipo = $_GET['tipo'] ?? null;
$referencia = $_GET['ref'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            if (!$tipo) {
                http_response_code(400);
                echo json_encode(['error' => 'Parámetro tipo requerido']);
                exit;
            }

            $sql = 'SELECT imagen_data, mime_type, tamanio_bytes, ancho, alto, actualizado_en
                    FROM imagenes
                    WHERE tipo_imagen = :tipo';
            $params = [':tipo' => $tipo];

            if ($referencia) {
                $sql .= ' AND referencia_id = :ref';
                $params[':ref'] = $referencia;
            } else {
                $sql .= ' AND (referencia_id IS NULL OR referencia_id = "")';
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $imagen = $stmt->fetch();

            if (!$imagen) {
                http_response_code(404);
                echo json_encode(['error' => 'Imagen no encontrada']);
                exit;
            }

            // Convertir a base64 para el frontend
            $base64 = base64_encode($imagen['imagen_data']);
            $dataUrl = 'data:' . $imagen['mime_type'] . ';base64,' . $base64;

            echo json_encode([
                'data_url' => $dataUrl,
                'mime_type' => $imagen['mime_type'],
                'size' => $imagen['tamanio_bytes'],
                'width' => $imagen['ancho'],
                'height' => $imagen['alto'],
                'updated_at' => $imagen['actualizado_en']
            ]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['tipo']) || empty($data['imagen'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Tipo e imagen son requeridos']);
                exit;
            }

            // Validar tipo
            $tiposValidos = ['logo_taller', 'foto_local', 'foto_servicio', 'foto_producto'];
            if (!in_array($data['tipo'], $tiposValidos)) {
                http_response_code(400);
                echo json_encode(['error' => 'Tipo de imagen no válido']);
                exit;
            }

            // Extraer información del data URL
            $imagenData = $data['imagen'];
            if (strpos($imagenData, 'data:') === 0) {
                preg_match('/^data:(image\/\w+);base64,(.*)$/', $imagenData, $matches);
                if (!$matches) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Formato de imagen no válido']);
                    exit;
                }
                $mimeType = $matches[1];
                $base64Data = $matches[2];
                $imagenBinaria = base64_decode($base64Data);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Formato de imagen no válido (debe ser data URL)']);
                exit;
            }

            $tamanio = strlen($imagenBinaria);

            // Validar tamaño (máximo 2MB en binario)
            if ($tamanio > 2 * 1024 * 1024) {
                http_response_code(413);
                echo json_encode([
                    'error' => 'Imagen demasiado grande',
                    'size' => round($tamanio / (1024 * 1024), 2) . ' MB',
                    'max' => '2 MB'
                ]);
                exit;
            }

            // Obtener dimensiones si están disponibles
            $ancho = $data['width'] ?? null;
            $alto = $data['height'] ?? null;
            $referencia = $data['referencia'] ?? null;

            // Insertar o actualizar
            $stmt = $pdo->prepare(
                'INSERT INTO imagenes (tipo_imagen, referencia_id, imagen_data, mime_type, tamanio_bytes, ancho, alto)
                 VALUES (:tipo, :ref, :data, :mime, :size, :width, :height)
                 ON DUPLICATE KEY UPDATE
                    imagen_data = :data2,
                    mime_type = :mime2,
                    tamanio_bytes = :size2,
                    ancho = :width2,
                    alto = :height2,
                    actualizado_en = CURRENT_TIMESTAMP'
            );

            $stmt->execute([
                ':tipo' => $data['tipo'],
                ':ref' => $referencia,
                ':data' => $imagenBinaria,
                ':data2' => $imagenBinaria,
                ':mime' => $mimeType,
                ':mime2' => $mimeType,
                ':size' => $tamanio,
                ':size2' => $tamanio,
                ':width' => $ancho,
                ':width2' => $ancho,
                ':height' => $alto,
                ':height2' => $alto
            ]);

            http_response_code(201);
            echo json_encode([
                'ok' => true,
                'mensaje' => 'Imagen guardada exitosamente',
                'size' => round($tamanio / 1024, 2) . ' KB'
            ]);
            break;

        case 'DELETE':
            if (!$tipo) {
                http_response_code(400);
                echo json_encode(['error' => 'Parámetro tipo requerido']);
                exit;
            }

            $sql = 'DELETE FROM imagenes WHERE tipo_imagen = :tipo';
            $params = [':tipo' => $tipo];

            if ($referencia) {
                $sql .= ' AND referencia_id = :ref';
                $params[':ref'] = $referencia;
            } else {
                $sql .= ' AND (referencia_id IS NULL OR referencia_id = "")';
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Imagen eliminada exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    error_log('Error en api_imagenes.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
