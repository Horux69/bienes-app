<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

requireAuth();

$tipo = $_GET['tipo'] ?? '';

function normalizarFilas(array $filas): array
{
    foreach ($filas as &$fila) {
        if (isset($fila['id'])) {
            $fila['id'] = (int) $fila['id'];
        }
        if (isset($fila['municipio_id'])) {
            $fila['municipio_id'] = (int) $fila['municipio_id'];
        }
        if (isset($fila['juzgado_id'])) {
            $fila['juzgado_id'] = (int) $fila['juzgado_id'];
        }
    }
    unset($fila);

    return $filas;
}

try {
    $pdo = getPDO();

    switch ($tipo) {
        case 'municipios':
            $stmt = $pdo->query('SELECT id, nombre FROM municipios WHERE activo = true ORDER BY nombre');
            echo json_encode(['ok' => true, 'items' => normalizarFilas($stmt->fetchAll())]);
            break;

        case 'juzgados':
            $municipioId = (int) ($_GET['municipio_id'] ?? 0);
            if (!$municipioId) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'municipio_id requerido.']);
                exit;
            }
            $stmt = $pdo->prepare('
                SELECT id, nombre FROM juzgados
                WHERE municipio_id = ? AND activo = true
                ORDER BY nombre
            ');
            $stmt->execute([$municipioId]);
            echo json_encode(['ok' => true, 'items' => normalizarFilas($stmt->fetchAll())]);
            break;

        case 'responsables':
            $juzgadoId = (int) ($_GET['juzgado_id'] ?? 0);
            if (!$juzgadoId) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'juzgado_id requerido.']);
                exit;
            }
            $stmt = $pdo->prepare('
                SELECT id, nombre FROM responsables
                WHERE juzgado_id = ? AND activo = true
                ORDER BY nombre
            ');
            $stmt->execute([$juzgadoId]);
            echo json_encode(['ok' => true, 'items' => normalizarFilas($stmt->fetchAll())]);
            break;

        case 'tipos_bienes':
            $stmt = $pdo->query('SELECT id, nombre, unidad FROM tipos_bienes WHERE activo = true ORDER BY id');
            echo json_encode(['ok' => true, 'items' => normalizarFilas($stmt->fetchAll())]);
            break;

        case 'perifericos':
            $stmt = $pdo->query('SELECT id, nombre FROM perifericos WHERE activo = true ORDER BY nombre');
            echo json_encode(['ok' => true, 'items' => normalizarFilas($stmt->fetchAll())]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Tipo no válido. Use: municipios, juzgados, responsables, tipos_bienes, perifericos.',
            ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
