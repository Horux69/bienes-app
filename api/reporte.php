<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';

$usuario = requireAdmin();
$pdo = getPDO();
$filtros = filtrosDesdeRequest($_GET);

if (isset($_GET['preview'])) {
    header('Content-Type: application/json; charset=utf-8');
    $total = contarRegistros($pdo, $filtros);
    $limite = 5000;
    echo json_encode([
        'ok' => true,
        'total' => $total,
        'exportara' => min($total, $limite),
        'limite' => $limite,
        'filtros' => describirFiltrosInforme($pdo, $filtros),
        'truncado' => $total > $limite,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => 'Dependencias no instaladas. Ejecute composer install en el servidor.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $autoload;
require_once __DIR__ . '/../reporte_excel.php';

try {
    $registros = enriquecerRegistrosInforme(
        $pdo,
        listarRegistros($pdo, $filtros, 5000)
    );

    $meta = [
        'generado_en' => date('Y-m-d H:i:s'),
        'usuario'     => $usuario['nombre'] ?? '',
        'email'       => $usuario['email'] ?? '',
        'filtros'     => describirFiltrosInforme($pdo, $filtros),
        'total'       => count($registros),
    ];

    $archivo = generarInformeExcel($registros, $meta);
    $nombre = 'informe-bienes_' . date('Y-m-d_His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Content-Length: ' . filesize($archivo));
    header('Cache-Control: no-store');

    readfile($archivo);
    @unlink($archivo);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => 'Error al generar el informe: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
