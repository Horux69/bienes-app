<?php

require_once __DIR__ . '/db.php';

function generarCodigoTrazabilidad(PDO $pdo): string
{
    $anio = date('Y');
    $n = (int) $pdo->query("SELECT nextval('seq_trazabilidad')")->fetchColumn();
    return sprintf('TRZ-%s-%06d', $anio, $n);
}

function validarRegistro(array $data): array
{
    $errores = [];
    $requeridos = [
        'municipio_id'   => 'Municipio',
        'juzgado_id'     => 'Juzgado',
        'responsable_id' => 'Responsable',
        'tipo_bien_id'   => 'Tipo de bien',
        'fecha_registro' => 'Fecha de registro',
    ];

    foreach ($requeridos as $campo => $etiqueta) {
        if (empty($data[$campo])) {
            $errores[] = "El campo {$etiqueta} es obligatorio.";
        }
    }

    $cantidad = $data['cantidad'] ?? null;
    if ($cantidad === null || $cantidad === '' || !is_numeric($cantidad) || (int) $cantidad < 1) {
        $errores[] = 'La cantidad debe ser un número mayor a 0.';
    }

    if (!empty($data['perifericos']) && is_array($data['perifericos'])) {
        foreach ($data['perifericos'] as $p) {
            if (empty($p['periferico_id'])) {
                $errores[] = 'Periférico inválido en la lista.';
                break;
            }
            if (!isset($p['cantidad']) || (int) $p['cantidad'] < 1) {
                $errores[] = 'Cada periférico debe tener cantidad mayor a 0.';
                break;
            }
        }
    }

    return $errores;
}

function validarCoherenciaCatalogos(PDO $pdo, array $data): array
{
    $errores = [];
    $municipioId = (int) ($data['municipio_id'] ?? 0);
    $juzgadoId = (int) ($data['juzgado_id'] ?? 0);
    $responsableId = (int) ($data['responsable_id'] ?? 0);

    $stmt = $pdo->prepare('
        SELECT j.id, j.municipio_id, j.nombre AS juzgado_nombre, m.nombre AS municipio_nombre
        FROM juzgados j
        INNER JOIN municipios m ON m.id = j.municipio_id
        WHERE j.id = ? AND j.activo = true AND m.activo = true
    ');
    $stmt->execute([$juzgadoId]);
    $juzgado = $stmt->fetch();

    if (!$juzgado) {
        $errores[] = 'Juzgado no válido o inactivo.';
        return $errores;
    }

    if ((int) $juzgado['municipio_id'] !== $municipioId) {
        $errores[] = 'El juzgado no pertenece al municipio seleccionado.';
    }

    $stmt = $pdo->prepare('
        SELECT r.id, r.nombre AS responsable_nombre
        FROM responsables r
        WHERE r.id = ? AND r.juzgado_id = ? AND r.activo = true
    ');
    $stmt->execute([$responsableId, $juzgadoId]);
    $responsable = $stmt->fetch();

    if (!$responsable) {
        $errores[] = 'Responsable no válido o inactivo para el juzgado seleccionado.';
        return $errores;
    }

    $stmt = $pdo->prepare('SELECT id FROM tipos_bienes WHERE id = ? AND activo = true');
    $stmt->execute([(int) ($data['tipo_bien_id'] ?? 0)]);
    if (!$stmt->fetch()) {
        $errores[] = 'Tipo de bien no válido o inactivo.';
    }

    return [
        'errores' => $errores,
        'snapshot' => [
            'municipio_nombre'   => $juzgado['municipio_nombre'],
            'juzgado_nombre'     => $juzgado['juzgado_nombre'],
            'responsable_nombre' => $responsable['responsable_nombre'],
        ],
    ];
}

function obtenerRegistroCompleto(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('
        SELECT rb.*, tb.nombre AS tipo_bien_nombre, tb.unidad AS tipo_bien_unidad
        FROM registros_bienes rb
        INNER JOIN tipos_bienes tb ON tb.id = rb.tipo_bien_id
        WHERE rb.id = ?
    ');
    $stmt->execute([$id]);
    $registro = $stmt->fetch();
    if (!$registro) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT rp.periferico_id, rp.cantidad, p.nombre
        FROM registro_perifericos rp
        INNER JOIN perifericos p ON p.id = rp.periferico_id
        WHERE rp.registro_bien_id = ?
        ORDER BY p.nombre
    ');
    $stmt->execute([$id]);
    $registro['perifericos'] = $stmt->fetchAll();

    $stmt = $pdo->prepare('
        SELECT id, ruta, nombre_archivo, created_at
        FROM fotos_bienes
        WHERE registro_bien_id = ?
        ORDER BY created_at
    ');
    $stmt->execute([$id]);
    $registro['fotos'] = aplicarUrlsFotos($stmt->fetchAll());

    return $registro;
}

function aplicarUrlsFotos(array $fotos): array
{
    require_once __DIR__ . '/storage.php';

    foreach ($fotos as &$foto) {
        $foto['ruta'] = urlFotoVisible($foto['ruta']);
    }
    unset($foto);

    return $fotos;
}

function filtrosDesdeRequest(array $source): array
{
    return array_filter([
        'municipio_id'   => $source['municipio_id'] ?? null,
        'juzgado_id'     => $source['juzgado_id'] ?? null,
        'responsable_id' => $source['responsable_id'] ?? null,
        'tipo_bien_id'   => $source['tipo_bien_id'] ?? null,
        'fecha_desde'    => $source['fecha_desde'] ?? null,
        'fecha_hasta'    => $source['fecha_hasta'] ?? null,
        'periferico_id'  => $source['periferico_id'] ?? null,
        'q'              => trim((string) ($source['q'] ?? '')) ?: null,
    ], fn ($v) => $v !== null && $v !== '');
}

function buildRegistrosWhereClause(array $filtros): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($filtros['municipio_id'])) {
        $where[] = 'rb.municipio_id = ?';
        $params[] = (int) $filtros['municipio_id'];
    }
    if (!empty($filtros['juzgado_id'])) {
        $where[] = 'rb.juzgado_id = ?';
        $params[] = (int) $filtros['juzgado_id'];
    }
    if (!empty($filtros['responsable_id'])) {
        $where[] = 'rb.responsable_id = ?';
        $params[] = (int) $filtros['responsable_id'];
    }
    if (!empty($filtros['tipo_bien_id'])) {
        $where[] = 'rb.tipo_bien_id = ?';
        $params[] = (int) $filtros['tipo_bien_id'];
    }
    if (!empty($filtros['fecha_desde'])) {
        $where[] = 'rb.fecha_registro >= ?';
        $params[] = $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $where[] = 'rb.fecha_registro <= ?';
        $params[] = $filtros['fecha_hasta'];
    }
    if (!empty($filtros['periferico_id'])) {
        $where[] = 'EXISTS (
            SELECT 1 FROM registro_perifericos rp
            WHERE rp.registro_bien_id = rb.id AND rp.periferico_id = ?
        )';
        $params[] = (int) $filtros['periferico_id'];
    }
    if (!empty($filtros['q'])) {
        $where[] = '(
            rb.codigo ILIKE ? OR rb.municipio_nombre ILIKE ? OR rb.juzgado_nombre ILIKE ?
            OR rb.responsable_nombre ILIKE ? OR tb.nombre ILIKE ? OR rb.observaciones ILIKE ?
        )';
        $like = '%' . $filtros['q'] . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    return [$where, $params];
}

function contarRegistros(PDO $pdo, array $filtros = []): int
{
    [$where, $params] = buildRegistrosWhereClause($filtros);
    $sql = '
        SELECT COUNT(*)
        FROM registros_bienes rb
        INNER JOIN tipos_bienes tb ON tb.id = rb.tipo_bien_id
        WHERE ' . implode(' AND ', $where);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function listarRegistros(PDO $pdo, array $filtros = [], int $limite = 500): array
{
    [$where, $params] = buildRegistrosWhereClause($filtros);
    $limite = max(1, min(5000, $limite));

    $sql = '
        SELECT rb.*, tb.nombre AS tipo_bien_nombre, tb.unidad AS tipo_bien_unidad,
               (SELECT ruta FROM fotos_bienes fb WHERE fb.registro_bien_id = rb.id ORDER BY fb.created_at LIMIT 1) AS foto_principal,
               (SELECT COALESCE(string_agg(rp.periferico_id::text, \',\'), \'\')
                FROM registro_perifericos rp WHERE rp.registro_bien_id = rb.id) AS periferico_ids
        FROM registros_bienes rb
        INNER JOIN tipos_bienes tb ON tb.id = rb.tipo_bien_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY rb.fecha_registro DESC, rb.created_at DESC
        LIMIT ' . $limite;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registros = $stmt->fetchAll();

    require_once __DIR__ . '/storage.php';
    foreach ($registros as &$registro) {
        if (!empty($registro['foto_principal'])) {
            $registro['foto_principal'] = urlFotoVisible($registro['foto_principal']);
        }
    }
    unset($registro);

    return $registros;
}

function urlBaseApp(): string
{
    $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = rtrim(dirname($script), '/\\');
    if (str_ends_with(strtolower($base), '/api')) {
        $base = dirname($base);
    }
    if ($base === '/' || $base === '\\' || $base === '.') {
        $base = '';
    }

    return $scheme . '://' . $host . $base;
}

function urlFotoAbsoluta(string $ruta): string
{
    require_once __DIR__ . '/storage.php';
    $rel = urlFotoVisible($ruta);
    if ($rel === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $rel)) {
        return $rel;
    }

    return urlBaseApp() . '/' . ltrim($rel, '/');
}

function nombreCatalogo(PDO $pdo, string $tabla, int $id): string
{
    $permitidas = [
        'municipios'    => 'municipios',
        'juzgados'      => 'juzgados',
        'responsables'  => 'responsables',
        'tipos_bienes'  => 'tipos_bienes',
        'perifericos'   => 'perifericos',
    ];
    if (!isset($permitidas[$tabla])) {
        return (string) $id;
    }

    $stmt = $pdo->prepare('SELECT nombre FROM ' . $permitidas[$tabla] . ' WHERE id = ?');
    $stmt->execute([$id]);

    return (string) ($stmt->fetchColumn() ?: $id);
}

function describirFiltrosInforme(PDO $pdo, array $filtros): string
{
    $partes = [];

    if (!empty($filtros['municipio_id'])) {
        $partes[] = 'Municipio: ' . nombreCatalogo($pdo, 'municipios', (int) $filtros['municipio_id']);
    }
    if (!empty($filtros['juzgado_id'])) {
        $partes[] = 'Juzgado: ' . nombreCatalogo($pdo, 'juzgados', (int) $filtros['juzgado_id']);
    }
    if (!empty($filtros['responsable_id'])) {
        $partes[] = 'Responsable: ' . nombreCatalogo($pdo, 'responsables', (int) $filtros['responsable_id']);
    }
    if (!empty($filtros['tipo_bien_id'])) {
        $partes[] = 'Tipo: ' . nombreCatalogo($pdo, 'tipos_bienes', (int) $filtros['tipo_bien_id']);
    }
    if (!empty($filtros['periferico_id'])) {
        $partes[] = 'Periférico: ' . nombreCatalogo($pdo, 'perifericos', (int) $filtros['periferico_id']);
    }
    if (!empty($filtros['fecha_desde'])) {
        $partes[] = 'Desde: ' . $filtros['fecha_desde'];
    }
    if (!empty($filtros['fecha_hasta'])) {
        $partes[] = 'Hasta: ' . $filtros['fecha_hasta'];
    }
    if (!empty($filtros['q'])) {
        $partes[] = 'Búsqueda: "' . $filtros['q'] . '"';
    }

    return $partes ? implode(' | ', $partes) : 'Todos los registros (sin filtros)';
}

function enriquecerRegistrosInforme(PDO $pdo, array $registros): array
{
    if ($registros === []) {
        return [];
    }

    $ids = array_map('intval', array_column($registros, 'id'));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT rp.registro_bien_id, p.nombre, rp.cantidad
        FROM registro_perifericos rp
        INNER JOIN perifericos p ON p.id = rp.periferico_id
        WHERE rp.registro_bien_id IN ($placeholders)
        ORDER BY p.nombre
    ");
    $stmt->execute($ids);
    $perifPorRegistro = [];
    foreach ($stmt->fetchAll() as $row) {
        $perifPorRegistro[$row['registro_bien_id']][] = $row['nombre'] . ' (' . $row['cantidad'] . ')';
    }

    $stmt = $pdo->prepare("
        SELECT registro_bien_id, ruta
        FROM fotos_bienes
        WHERE registro_bien_id IN ($placeholders)
        ORDER BY created_at
    ");
    $stmt->execute($ids);
    $fotosPorRegistro = [];
    foreach ($stmt->fetchAll() as $row) {
        $url = urlFotoAbsoluta($row['ruta']);
        if ($url !== '') {
            $fotosPorRegistro[$row['registro_bien_id']][] = $url;
        }
    }

    foreach ($registros as &$registro) {
        $id = (int) $registro['id'];
        $registro['perifericos_texto'] = implode(', ', $perifPorRegistro[$id] ?? []) ?: '—';
        $registro['fotos_urls'] = $fotosPorRegistro[$id] ?? [];
        $registro['fotos_urls_texto'] = implode("\n", $registro['fotos_urls']);
        $registro['num_fotos'] = count($registro['fotos_urls']);
    }
    unset($registro);

    return $registros;
}

function guardarPerifericos(PDO $pdo, int $registroId, array $perifericos): void
{
    $pdo->prepare('DELETE FROM registro_perifericos WHERE registro_bien_id = ?')->execute([$registroId]);

    if (empty($perifericos)) {
        return;
    }

    $stmt = $pdo->prepare('
        INSERT INTO registro_perifericos (registro_bien_id, periferico_id, cantidad)
        VALUES (?, ?, ?)
    ');

    foreach ($perifericos as $p) {
        $stmtCheck = $pdo->prepare('SELECT id FROM perifericos WHERE id = ? AND activo = true');
        $stmtCheck->execute([(int) $p['periferico_id']]);
        if (!$stmtCheck->fetch()) {
            continue;
        }
        $stmt->execute([$registroId, (int) $p['periferico_id'], (int) $p['cantidad']]);
    }
}

function guardarFotos(PDO $pdo, int $registroId, array $fotosBase64): void
{
    require_once __DIR__ . '/storage.php';

    $stmt = $pdo->prepare('
        INSERT INTO fotos_bienes (registro_bien_id, ruta, nombre_archivo)
        VALUES (?, ?, ?)
    ');

    foreach ($fotosBase64 as $foto) {
        if (empty($foto)) {
            continue;
        }
        $nombre = uniqid('bien_', true) . '.jpg';
        $ruta = subirFotoSupabase($foto, $nombre);
        $stmt->execute([$registroId, $ruta, $nombre]);
    }
}

function eliminarFotos(PDO $pdo, int $registroId, array $fotoIds): void
{
    if (empty($fotoIds)) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($fotoIds), '?'));
    $params = array_merge([$registroId], array_map('intval', $fotoIds));
    $pdo->prepare("DELETE FROM fotos_bienes WHERE registro_bien_id = ? AND id IN ($placeholders)")
        ->execute($params);
}
