<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

requireAdmin();

$metodo = $_SERVER['REQUEST_METHOD'];
$tipo = $_GET['tipo'] ?? '';

$tiposValidos = ['municipios', 'juzgados', 'responsables', 'tipos_bienes', 'perifericos'];

function leerJson(): array
{
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

function responderError(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    echo json_encode(['ok' => false, 'error' => $mensaje]);
    exit;
}

function validarTipo(string $tipo, array $tiposValidos): void
{
    if (!in_array($tipo, $tiposValidos, true)) {
        responderError(400, 'Tipo no válido.');
    }
}

function nombreRequerido(array $data): string
{
    $nombre = trim($data['nombre'] ?? '');
    if ($nombre === '') {
        responderError(422, 'El nombre es obligatorio.');
    }
    return $nombre;
}

try {
    $pdo = getPDO();

    if ($metodo === 'GET') {
        validarTipo($tipo, $tiposValidos);

        switch ($tipo) {
            case 'municipios':
                $stmt = $pdo->query('SELECT id, nombre, activo FROM municipios ORDER BY nombre');
                break;
            case 'juzgados':
                $stmt = $pdo->query('
                    SELECT j.id, j.nombre, j.municipio_id, j.activo, m.nombre AS municipio_nombre
                    FROM juzgados j
                    INNER JOIN municipios m ON m.id = j.municipio_id
                    ORDER BY m.nombre, j.nombre
                ');
                break;
            case 'responsables':
                $stmt = $pdo->query('
                    SELECT r.id, r.nombre, r.juzgado_id, r.activo,
                           j.nombre AS juzgado_nombre, m.nombre AS municipio_nombre
                    FROM responsables r
                    INNER JOIN juzgados j ON j.id = r.juzgado_id
                    INNER JOIN municipios m ON m.id = j.municipio_id
                    ORDER BY m.nombre, j.nombre, r.nombre
                ');
                break;
            case 'tipos_bienes':
                $stmt = $pdo->query('SELECT id, nombre, unidad, activo FROM tipos_bienes ORDER BY nombre');
                break;
            case 'perifericos':
                $stmt = $pdo->query('SELECT id, nombre, activo FROM perifericos ORDER BY nombre');
                break;
        }

        echo json_encode(['ok' => true, 'items' => $stmt->fetchAll()]);
        exit;
    }

    if ($metodo === 'POST') {
        $data = leerJson();
        $tipo = $data['tipo'] ?? '';
        validarTipo($tipo, $tiposValidos);
        $nombre = nombreRequerido($data);

        switch ($tipo) {
            case 'municipios':
                $stmt = $pdo->prepare('INSERT INTO municipios (nombre) VALUES (?) RETURNING id, nombre, activo');
                $stmt->execute([$nombre]);
                break;
            case 'juzgados':
                $municipioId = (int) ($data['municipio_id'] ?? 0);
                if (!$municipioId) {
                    responderError(422, 'municipio_id es obligatorio.');
                }
                $stmt = $pdo->prepare('
                    INSERT INTO juzgados (municipio_id, nombre) VALUES (?, ?)
                    RETURNING id, nombre, municipio_id, activo
                ');
                $stmt->execute([$municipioId, $nombre]);
                break;
            case 'responsables':
                $juzgadoId = (int) ($data['juzgado_id'] ?? 0);
                if (!$juzgadoId) {
                    responderError(422, 'juzgado_id es obligatorio.');
                }
                $stmt = $pdo->prepare('
                    INSERT INTO responsables (juzgado_id, nombre) VALUES (?, ?)
                    RETURNING id, nombre, juzgado_id, activo
                ');
                $stmt->execute([$juzgadoId, $nombre]);
                break;
            case 'tipos_bienes':
                $unidad = trim($data['unidad'] ?? 'unidad') ?: 'unidad';
                $stmt = $pdo->prepare('
                    INSERT INTO tipos_bienes (nombre, unidad) VALUES (?, ?)
                    RETURNING id, nombre, unidad, activo
                ');
                $stmt->execute([$nombre, $unidad]);
                break;
            case 'perifericos':
                $stmt = $pdo->prepare('INSERT INTO perifericos (nombre) VALUES (?) RETURNING id, nombre, activo');
                $stmt->execute([$nombre]);
                break;
        }

        echo json_encode(['ok' => true, 'item' => $stmt->fetch(), 'mensaje' => 'Registro creado correctamente.']);
        exit;
    }

    if ($metodo === 'PUT') {
        $data = leerJson();
        $tipo = $data['tipo'] ?? '';
        $id = (int) ($data['id'] ?? 0);
        validarTipo($tipo, $tiposValidos);
        if (!$id) {
            responderError(400, 'ID requerido.');
        }

        $nombre = nombreRequerido($data);
        $activo = array_key_exists('activo', $data) ? (bool) $data['activo'] : true;

        switch ($tipo) {
            case 'municipios':
                $stmt = $pdo->prepare('
                    UPDATE municipios SET nombre = ?, activo = ?
                    WHERE id = ? RETURNING id, nombre, activo
                ');
                $stmt->execute([$nombre, $activo, $id]);
                break;
            case 'juzgados':
                $municipioId = (int) ($data['municipio_id'] ?? 0);
                if (!$municipioId) {
                    responderError(422, 'municipio_id es obligatorio.');
                }
                $stmt = $pdo->prepare('
                    UPDATE juzgados SET municipio_id = ?, nombre = ?, activo = ?
                    WHERE id = ? RETURNING id, nombre, municipio_id, activo
                ');
                $stmt->execute([$municipioId, $nombre, $activo, $id]);
                break;
            case 'responsables':
                $juzgadoId = (int) ($data['juzgado_id'] ?? 0);
                if (!$juzgadoId) {
                    responderError(422, 'juzgado_id es obligatorio.');
                }
                $stmt = $pdo->prepare('
                    UPDATE responsables SET juzgado_id = ?, nombre = ?, activo = ?
                    WHERE id = ? RETURNING id, nombre, juzgado_id, activo
                ');
                $stmt->execute([$juzgadoId, $nombre, $activo, $id]);
                break;
            case 'tipos_bienes':
                $unidad = trim($data['unidad'] ?? 'unidad') ?: 'unidad';
                $stmt = $pdo->prepare('
                    UPDATE tipos_bienes SET nombre = ?, unidad = ?, activo = ?
                    WHERE id = ? RETURNING id, nombre, unidad, activo
                ');
                $stmt->execute([$nombre, $unidad, $activo, $id]);
                break;
            case 'perifericos':
                $stmt = $pdo->prepare('
                    UPDATE perifericos SET nombre = ?, activo = ?
                    WHERE id = ? RETURNING id, nombre, activo
                ');
                $stmt->execute([$nombre, $activo, $id]);
                break;
        }

        $item = $stmt->fetch();
        if (!$item) {
            responderError(404, 'Registro no encontrado.');
        }

        echo json_encode(['ok' => true, 'item' => $item, 'mensaje' => 'Registro actualizado correctamente.']);
        exit;
    }

    if ($metodo === 'DELETE') {
        validarTipo($tipo, $tiposValidos);
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            responderError(400, 'ID requerido.');
        }

        $tablas = [
            'municipios' => 'municipios',
            'juzgados' => 'juzgados',
            'responsables' => 'responsables',
            'tipos_bienes' => 'tipos_bienes',
            'perifericos' => 'perifericos',
        ];

        $stmt = $pdo->prepare("UPDATE {$tablas[$tipo]} SET activo = false WHERE id = ? RETURNING id");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            responderError(404, 'Registro no encontrado.');
        }

        echo json_encode(['ok' => true, 'mensaje' => 'Registro desactivado correctamente.']);
        exit;
    }

    responderError(405, 'Método no permitido.');
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')) {
        responderError(422, 'Ya existe un registro con ese nombre.');
    }
    responderError(500, $e->getMessage());
} catch (Throwable $e) {
    responderError(500, $e->getMessage());
}
