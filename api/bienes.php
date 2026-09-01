<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';

requireAuth();

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getPDO();

    switch ($metodo) {
        case 'GET':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            if ($id) {
                $registro = obtenerRegistroCompleto($pdo, $id);
                if (!$registro) {
                    http_response_code(404);
                    echo json_encode(['ok' => false, 'error' => 'Registro no encontrado.']);
                    exit;
                }
                echo json_encode(['ok' => true, 'registro' => $registro]);
            } else {
                $filtros = array_filter([
                    'municipio_id' => $_GET['municipio_id'] ?? null,
                    'juzgado_id' => $_GET['juzgado_id'] ?? null,
                    'responsable_id' => $_GET['responsable_id'] ?? null,
                    'tipo_bien_id' => $_GET['tipo_bien_id'] ?? null,
                    'fecha_desde' => $_GET['fecha_desde'] ?? null,
                    'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
                    'periferico_id' => $_GET['periferico_id'] ?? null,
                    'q' => trim($_GET['q'] ?? '') ?: null,
                ], fn ($v) => $v !== null && $v !== '');
                echo json_encode(['ok' => true, 'registros' => listarRegistros($pdo, $filtros)]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            $errores = validarRegistro($data);
            if ($errores) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => implode(' ', $errores)]);
                exit;
            }

            $coherencia = validarCoherenciaCatalogos($pdo, $data);
            if ($coherencia['errores']) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => implode(' ', $coherencia['errores'])]);
                exit;
            }

            $codigo = generarCodigoTrazabilidad($pdo);
            $snap = $coherencia['snapshot'];

            $checklist = checklistDesdeRequest($data);

            $stmt = $pdo->prepare('
                INSERT INTO registros_bienes (
                  codigo, municipio_id, juzgado_id, responsable_id, tipo_bien_id,
                  cantidad, observaciones, fecha_registro,
                  municipio_nombre, juzgado_nombre, responsable_nombre,
                  limpieza, embalado, rotulado, foto
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING id
            ');
            $stmt->execute([
                $codigo,
                (int) $data['municipio_id'],
                (int) $data['juzgado_id'],
                (int) $data['responsable_id'],
                (int) $data['tipo_bien_id'],
                (int) $data['cantidad'],
                trim($data['observaciones'] ?? '') ?: null,
                $data['fecha_registro'],
                $snap['municipio_nombre'],
                $snap['juzgado_nombre'],
                $snap['responsable_nombre'],
                $checklist['limpieza'],
                $checklist['embalado'],
                $checklist['rotulado'],
                false,
            ]);

            $registroId = (int) $stmt->fetchColumn();
            guardarPerifericos($pdo, $registroId, $data['perifericos'] ?? []);
            guardarFotos($pdo, $registroId, $data['fotos'] ?? []);
            sincronizarCheckFoto($pdo, $registroId);

            $registro = obtenerRegistroCompleto($pdo, $registroId);
            echo json_encode(['ok' => true, 'registro' => $registro, 'mensaje' => 'Bien registrado correctamente.']);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            $id = isset($data['id']) ? (int) $data['id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'ID requerido para actualizar.']);
                exit;
            }

            if (!obtenerRegistroCompleto($pdo, $id)) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Registro no encontrado.']);
                exit;
            }

            $errores = validarRegistro($data);
            if ($errores) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => implode(' ', $errores)]);
                exit;
            }

            $coherencia = validarCoherenciaCatalogos($pdo, $data);
            if ($coherencia['errores']) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => implode(' ', $coherencia['errores'])]);
                exit;
            }

            $snap = $coherencia['snapshot'];
            $checklist = checklistDesdeRequest($data);

            $stmt = $pdo->prepare('
                UPDATE registros_bienes SET
                  municipio_id = ?, juzgado_id = ?, responsable_id = ?, tipo_bien_id = ?,
                  cantidad = ?, observaciones = ?, fecha_registro = ?,
                  municipio_nombre = ?, juzgado_nombre = ?, responsable_nombre = ?,
                  limpieza = ?, embalado = ?, rotulado = ?,
                  updated_at = now()
                WHERE id = ?
            ');
            $stmt->execute([
                (int) $data['municipio_id'],
                (int) $data['juzgado_id'],
                (int) $data['responsable_id'],
                (int) $data['tipo_bien_id'],
                (int) $data['cantidad'],
                trim($data['observaciones'] ?? '') ?: null,
                $data['fecha_registro'],
                $snap['municipio_nombre'],
                $snap['juzgado_nombre'],
                $snap['responsable_nombre'],
                $checklist['limpieza'],
                $checklist['embalado'],
                $checklist['rotulado'],
                $id,
            ]);

            guardarPerifericos($pdo, $id, $data['perifericos'] ?? []);
            eliminarFotos($pdo, $id, $data['fotos_eliminar'] ?? []);
            guardarFotos($pdo, $id, $data['fotos_nuevas'] ?? []);
            sincronizarCheckFoto($pdo, $id);

            $registro = obtenerRegistroCompleto($pdo, $id);
            echo json_encode(['ok' => true, 'registro' => $registro, 'mensaje' => 'Registro actualizado correctamente.']);
            break;

        case 'DELETE':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'ID requerido para eliminar.']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM registros_bienes WHERE id = ? RETURNING id');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Registro no encontrado.']);
                exit;
            }

            echo json_encode(['ok' => true, 'mensaje' => 'Registro eliminado correctamente.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
