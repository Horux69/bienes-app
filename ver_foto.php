<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/storage.php';

$codigo = trim($_GET['codigo'] ?? '');
$fotoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($codigo === '' || $fotoId <= 0) {
    http_response_code(400);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('
        SELECT fb.ruta
        FROM fotos_bienes fb
        INNER JOIN registros_bienes rb ON rb.id = fb.registro_bien_id
        WHERE rb.codigo = ? AND fb.id = ?
    ');
    $stmt->execute([$codigo, $fotoId]);
    $ruta = $stmt->fetchColumn();

    if (!$ruta) {
        http_response_code(404);
        exit;
    }

    streamFotoSupabase((string) $ruta);
} catch (Throwable $e) {
    http_response_code(500);
    exit;
}
