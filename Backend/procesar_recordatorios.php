<?php
/**
 * Procesa recordatorios WhatsApp. Ejecutar únicamente desde una tarea CLI diaria.
 * Requiere AUTOCTRL_WA_TOKEN, AUTOCTRL_WA_PHONE_NUMBER_ID y AUTOCTRL_WA_TEMPLATE.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/db.php';

const STORAGE_KEY = 'autocontrol_data_v1';
$graphVersion = getenv('AUTOCTRL_WA_GRAPH_VERSION') ?: 'v23.0';
$accessToken = getenv('AUTOCTRL_WA_TOKEN') ?: '';
$phoneNumberId = getenv('AUTOCTRL_WA_PHONE_NUMBER_ID') ?: '';
$templateName = getenv('AUTOCTRL_WA_TEMPLATE') ?: '';
$templateLanguage = getenv('AUTOCTRL_WA_TEMPLATE_LANGUAGE') ?: 'es_CO';
$now = new DateTimeImmutable('now', new DateTimeZone('America/Bogota'));
$today = $now->format('Y-m-d');

$pdo->exec('CREATE TABLE IF NOT EXISTS almacenamiento (clave VARCHAR(100) NOT NULL PRIMARY KEY, valor LONGTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS recordatorios_enviados (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        alerta_id VARCHAR(100) NOT NULL,
        regla_id VARCHAR(100) NOT NULL,
        fecha_programada DATE NOT NULL,
        enviado_en DATETIME NOT NULL,
        whatsapp_message_id VARCHAR(150) NULL,
        UNIQUE KEY uq_recordatorio (alerta_id, regla_id, fecha_programada)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$select = $pdo->prepare('SELECT valor FROM almacenamiento WHERE clave = :clave');
$select->execute([':clave' => STORAGE_KEY]);
$storedValue = $select->fetchColumn();
if (!$storedValue) {
    fwrite(STDERR, "No existe autocontrol_data_v1 en almacenamiento.\n");
    exit(1);
}
$data = json_decode($storedValue, true);
if (!is_array($data)) {
    fwrite(STDERR, "Los datos de AutoControl no contienen JSON válido.\n");
    exit(1);
}

$missing = [];
if ($accessToken === '') $missing[] = 'AUTOCTRL_WA_TOKEN';
if ($phoneNumberId === '') $missing[] = 'AUTOCTRL_WA_PHONE_NUMBER_ID';
if ($templateName === '') $missing[] = 'AUTOCTRL_WA_TEMPLATE';
$automation = [
    'activo' => count($missing) === 0,
    'ultimaRevision' => date(DATE_ATOM),
    'ultimoError' => $missing ? 'Falta configurar: ' . implode(', ', $missing) : null,
    'enviadosUltimaRevision' => 0
];

function fechaObjetivo(string $fechaServicio, array $regla): ?string {
    try {
        $zona = new DateTimeZone('America/Bogota');
        $servicio = new DateTimeImmutable($fechaServicio . ' 00:00:00', $zona);
        $momento = $regla['momento'] ?? 'antes';
        if ($momento === 'mismo-dia') return $servicio->format('Y-m-d H:i:s');
        $cantidad = max(0, (int)($regla['cantidad'] ?? 0));
        $direccion = $momento === 'despues' ? 1 : -1;
        if (($regla['unidad'] ?? 'dia') === 'mes') {
            $primerDia = $servicio->modify('first day of this month')->modify(($direccion * $cantidad) . ' months');
            $dia = min((int)$servicio->format('d'), (int)$primerDia->format('t'));
            return $primerDia->setDate((int)$primerDia->format('Y'), (int)$primerDia->format('m'), $dia)->format('Y-m-d H:i:s');
        }
        $unidad = $regla['unidad'] ?? 'dia';
        $multiplicadores = ['segundo'=>1, 'minuto'=>60, 'hora'=>3600];
        if (isset($multiplicadores[$unidad])) {
            $segundos = $direccion * $cantidad * $multiplicadores[$unidad];
            return $servicio->modify(($segundos >= 0 ? '+' : '') . $segundos . ' seconds')->format('Y-m-d H:i:s');
        }
        return $servicio->modify(($direccion * $cantidad) . ' days')->format('Y-m-d H:i:s');
    } catch (Throwable $error) {
        return null;
    }
}

function numeroWhatsApp(array $cliente): string {
    $raw = trim((string)($cliente['whatsapp'] ?? ''));
    if ($raw === '') $raw = (string)($cliente['telefono'] ?? '');
    $numero = preg_replace('/\\D+/', '', $raw);
    if (strlen($numero) === 10) $numero = '57' . $numero;
    return $numero;
}

function nombreServicios(array $ids, array $servicios): string {
    $nombres = [];
    foreach ($ids as $id) {
        foreach ($servicios as $servicio) {
            if ((string)($servicio['id'] ?? '') === (string)$id) {
                $nombres[] = (string)($servicio['nombre'] ?? 'Servicio');
                break;
            }
        }
    }
    return $nombres ? implode(', ', $nombres) : 'mantenimiento del vehículo';
}

function enviarPlantilla(string $to, array $parameters, string $version, string $phoneNumberId, string $token, string $template, string $language): array {
    if (!function_exists('curl_init')) return [false, null, 'La extensión cURL de PHP no está habilitada.'];
    $bodyParameters = array_map(static function (string $text): array {
        return ['type' => 'text', 'text' => $text];
    }, $parameters);
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'template',
        'template' => [
            'name' => $template,
            'language' => ['code' => $language],
            'components' => [['type' => 'body', 'parameters' => $bodyParameters]]
        ]
    ];
    $curl = curl_init('https://graph.facebook.com/' . rawurlencode($version) . '/' . rawurlencode($phoneNumberId) . '/messages');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25
    ]);
    $response = curl_exec($curl);
    $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    $decoded = is_string($response) ? json_decode($response, true) : null;
    if ($httpCode < 200 || $httpCode >= 300) {
        $message = $curlError ?: ($decoded['error']['message'] ?? 'WhatsApp rechazó el envío (HTTP ' . $httpCode . ').');
        return [false, null, $message];
    }
    return [true, $decoded['messages'][0]['id'] ?? null, null];
}

if (!$missing) {
    $clientes = $data['clientes'] ?? [];
    $servicios = $data['servicios'] ?? [];
    $rules = $data['config']['reglasRecordatorio'] ?? [];
    $findSent = $pdo->prepare('SELECT id FROM recordatorios_enviados WHERE alerta_id = :alerta AND regla_id = :regla AND fecha_programada = :fecha LIMIT 1');
    $saveSent = $pdo->prepare('INSERT IGNORE INTO recordatorios_enviados (alerta_id, regla_id, fecha_programada, enviado_en, whatsapp_message_id) VALUES (:alerta, :regla, :fecha, :enviado, :mensaje)');
    $hadError = false;

    foreach (($data['alertas'] ?? []) as &$alerta) {
        if (($alerta['estado'] ?? 'pendiente') === 'realizada' || empty($alerta['fecha'])) continue;
        if (($alerta['notificarWhatsapp'] ?? true) === false) continue;
        $cliente = null;
        foreach ($clientes as $candidate) {
            if ((string)($candidate['id'] ?? '') === (string)($alerta['clienteId'] ?? '')) { $cliente = $candidate; break; }
        }
        if (!$cliente || ($cliente['whatsappConsent'] ?? false) !== true) continue;
        $to = numeroWhatsApp($cliente);
        if ($to === '') continue;
        $serviceNames = nombreServicios($alerta['servicioIds'] ?? [], $servicios);
        $dueDate = (string)$alerta['fecha'];

        foreach ($rules as $rule) {
            if (empty($rule['activo']) || empty($rule['id'])) continue;
            $targetDateTime = fechaObjetivo($dueDate, $rule);
            if ($targetDateTime === null) continue;
            $target = new DateTimeImmutable($targetDateTime, new DateTimeZone('America/Bogota'));
            $unit = $rule['unidad'] ?? 'dia';
            if (in_array($unit, ['segundo','minuto','hora'], true) && ($rule['momento'] ?? '') !== 'mismo-dia') {
                if ($unit === 'segundo') {
                    if ($target->getTimestamp() > $now->getTimestamp() || $now->getTimestamp() - $target->getTimestamp() > 2) continue;
                } elseif ($target->format('Y-m-d H:i') !== $now->format('Y-m-d H:i')) {
                    continue;
                }
            } elseif ($target->format('Y-m-d') !== $today) {
                continue;
            }
            $targetDate = $target->format('Y-m-d');
            $findSent->execute([':alerta' => (string)$alerta['id'], ':regla' => (string)$rule['id'], ':fecha' => $targetDate]);
            if ($findSent->fetchColumn()) continue;

            $isOverdue = ($rule['momento'] ?? '') === 'despues';
            $statusText = $isOverdue
                ? 'Este servicio está vencido. Te recomendamos programar tu mantenimiento.'
                : 'Este es un recordatorio para que programes tu mantenimiento.';
            [$sent, $messageId, $sendError] = enviarPlantilla(
                $to,
                [(string)($cliente['nombre'] ?? 'Cliente'), $serviceNames, (new DateTimeImmutable($dueDate, new DateTimeZone('America/Bogota')))->format('d/m/Y'), $statusText],
                $graphVersion,
                $phoneNumberId,
                $accessToken,
                $templateName,
                $templateLanguage
            );
            if (!$sent) {
                $hadError = true;
                $automation['ultimoError'] = $sendError;
                fwrite(STDERR, 'No se pudo enviar alerta ' . (string)$alerta['id'] . ': ' . $sendError . "\n");
                continue;
            }
            $sentAt = date('Y-m-d H:i:s');
            $saveSent->execute([':alerta' => (string)$alerta['id'], ':regla' => (string)$rule['id'], ':fecha' => $targetDate, ':enviado' => $sentAt, ':mensaje' => $messageId]);
            $alerta['recordatoriosEnviados'][(string)$rule['id']] = ['fechaProgramada' => $targetDate, 'enviadoEn' => date(DATE_ATOM), 'messageId' => $messageId];
            $automation['enviadosUltimaRevision']++;
        }
    }
    unset($alerta);
    $automation['activo'] = !$hadError;
}

$data['config']['recordatoriosAutomaticos'] = $automation;
$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
$saveData = $pdo->prepare('INSERT INTO almacenamiento (clave, valor) VALUES (:clave, :valor) ON DUPLICATE KEY UPDATE valor = :valor2');
$saveData->execute([':clave' => STORAGE_KEY, ':valor' => $json, ':valor2' => $json]);

echo json_encode($automation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (!$automation['activo']) exit(2);
