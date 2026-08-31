<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';

try {
    $cfg = require __DIR__ . '/../config.php';
    $pdo = getPDO();
    $total = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

    echo json_encode([
        'ok' => true,
        'db_host' => $cfg['db']['host'] !== '' ? 'configurado' : 'vacío',
        'db_password' => $cfg['db']['password'] !== '' ? 'configurado' : 'vacío',
        'usuarios' => $total,
        'env_file' => is_readable(__DIR__ . '/../.env') ? 'sí' : 'no',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    $cfg = $GLOBALS['__app_config'] ?? require __DIR__ . '/../config.php';
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'db_host' => $cfg['db']['host'] !== '' ? 'configurado' : 'vacío',
        'db_password' => $cfg['db']['password'] !== '' ? 'configurado' : 'vacío',
        'env_file' => is_readable(__DIR__ . '/../.env') ? 'sí' : 'no',
    ]);
}
