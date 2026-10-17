<?php
/**
 * API para gestión de usuarios en AutoControl
 * Endpoints:
 * GET    /api_usuarios.php              - Listar todos los usuarios
 * GET    /api_usuarios.php?id=N         - Obtener un usuario específico
 * POST   /api_usuarios.php              - Crear nuevo usuario
 * PUT    /api_usuarios.php?id=N         - Actualizar usuario
 * DELETE /api_usuarios.php?id=N         - Eliminar usuario
 * POST   /api_usuarios.php?action=login - Autenticar usuario
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
$action = $_GET['action'] ?? null;

try {
    switch ($metodo) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare(
                    'SELECT u.id_usuario, u.id_cliente, u.usuario, u.estado, u.creado_en,
                            c.nombre, c.apellido, c.telefono, c.correo
                     FROM usuarios u
                     LEFT JOIN clientes c ON u.id_cliente = c.id_cliente
                     WHERE u.id_usuario = :id'
                );
                $stmt->execute([':id' => $id]);
                $usuario = $stmt->fetch();

                if (!$usuario) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuario no encontrado']);
                    exit;
                }

                echo json_encode($usuario);
            } else {
                $stmt = $pdo->query(
                    'SELECT u.id_usuario, u.id_cliente, u.usuario, u.estado, u.creado_en,
                            c.nombre, c.apellido
                     FROM usuarios u
                     LEFT JOIN clientes c ON u.id_cliente = c.id_cliente
                     ORDER BY u.usuario'
                );
                $usuarios = $stmt->fetchAll();
                echo json_encode($usuarios);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if ($action === 'login') {
                if (empty($data['usuario']) || empty($data['contrasena'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Usuario y contraseña son requeridos']);
                    exit;
                }

                $stmt = $pdo->prepare(
                    'SELECT u.*, c.nombre, c.apellido
                     FROM usuarios u
                     LEFT JOIN clientes c ON u.id_cliente = c.id_cliente
                     WHERE u.usuario = :usuario AND u.estado = "activo"'
                );
                $stmt->execute([':usuario' => $data['usuario']]);
                $usuario = $stmt->fetch();

                if (!$usuario || !password_verify($data['contrasena'], $usuario['contrasena'])) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Credenciales inválidas']);
                    exit;
                }

                unset($usuario['contrasena']);
                echo json_encode([
                    'ok' => true,
                    'usuario' => $usuario
                ]);
                exit;
            }

            if (empty($data['id_cliente']) || empty($data['usuario']) || empty($data['contrasena'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de cliente, usuario y contraseña son requeridos']);
                exit;
            }

            $contrasenaHash = password_hash($data['contrasena'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (id_cliente, usuario, contrasena, estado)
                 VALUES (:id_cliente, :usuario, :contrasena, :estado)'
            );

            $stmt->execute([
                ':id_cliente' => $data['id_cliente'],
                ':usuario' => $data['usuario'],
                ':contrasena' => $contrasenaHash,
                ':estado' => $data['estado'] ?? 'activo'
            ]);

            $nuevoId = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'ok' => true,
                'id' => $nuevoId,
                'mensaje' => 'Usuario creado exitosamente'
            ]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de usuario requerido']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (isset($data['contrasena']) && !empty($data['contrasena'])) {
                $contrasenaHash = password_hash($data['contrasena'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'UPDATE usuarios
                     SET id_cliente = :id_cliente, usuario = :usuario,
                         contrasena = :contrasena, estado = :estado
                     WHERE id_usuario = :id'
                );

                $stmt->execute([
                    ':id' => $id,
                    ':id_cliente' => $data['id_cliente'],
                    ':usuario' => $data['usuario'],
                    ':contrasena' => $contrasenaHash,
                    ':estado' => $data['estado'] ?? 'activo'
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE usuarios
                     SET id_cliente = :id_cliente, usuario = :usuario, estado = :estado
                     WHERE id_usuario = :id'
                );

                $stmt->execute([
                    ':id' => $id,
                    ':id_cliente' => $data['id_cliente'],
                    ':usuario' => $data['usuario'],
                    ':estado' => $data['estado'] ?? 'activo'
                ]);
            }

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Usuario actualizado exitosamente'
            ]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de usuario requerido']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode([
                'ok' => true,
                'mensaje' => 'Usuario eliminado exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (PDOException $e) {
    error_log('Error en api_usuarios.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
