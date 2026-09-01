<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$codigo = trim($_GET['codigo'] ?? '');
$registro = null;
$error = '';

try {
    if ($codigo !== '') {
        $pdo = getPDO();
        $registro = obtenerRegistroPorCodigo($pdo, $codigo);
        if (!$registro) {
            $error = 'No se encontró un bien con ese código de trazabilidad.';
        }
    }
} catch (Throwable $e) {
    $error = 'No se pudo consultar el registro.';
}

function fmtFecha(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }
    $partes = explode('-', substr($fecha, 0, 10));
    if (count($partes) === 3) {
        return $partes[2] . '/' . $partes[1] . '/' . $partes[0];
    }

    return $fecha;
}

function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $registro ? e($registro['codigo']) . ' · Trazabilidad' : 'Consulta de bien' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background: #f4f6f9; }
  .consulta-wrap { max-width: 640px; margin: 0 auto; padding: 1.25rem; }
  .consulta-card {
    background: #fff; border-radius: 14px; border: 1px solid #e5e9f0;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06); overflow: hidden;
  }
  .consulta-header {
    background: linear-gradient(135deg, #1f4e79, #2563a8);
    color: #fff; padding: 1.25rem 1.5rem;
  }
  .code-badge {
    display: inline-block; background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.25); border-radius: 999px;
    padding: .35rem .85rem; font-weight: 700; letter-spacing: .04em;
  }
  .consulta-body { padding: 1.25rem 1.5rem 1.5rem; }
  .detail-row { display: flex; gap: .75rem; padding: .55rem 0; border-bottom: 1px solid #eef2f7; }
  .detail-row:last-child { border-bottom: 0; }
  .detail-label { width: 38%; color: #64748b; font-size: .9rem; flex-shrink: 0; }
  .detail-value { flex: 1; font-weight: 500; color: #0f172a; }
  .check-pill {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .82rem; padding: .25rem .55rem; border-radius: 999px;
    background: #f1f5f9; color: #475569;
  }
  .check-pill.done { background: #dcfce7; color: #166534; }
  .check-pill.pending { background: #fef3c7; color: #92400e; }
  .fotos-section { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #eef2f7; }
  .fotos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: .75rem;
    margin-top: .75rem;
  }
  .foto-item {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    background: #f8fafc;
    cursor: pointer;
  }
  .foto-item img {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 3;
    object-fit: cover;
    background: #e2e8f0;
  }
  .foto-modal img { max-width: 100%; max-height: 80vh; object-fit: contain; }
</style>
</head>
<body>
<div class="consulta-wrap">
  <?php if ($error): ?>
    <div class="consulta-card">
      <div class="consulta-header"><h1 class="h5 mb-0">Consulta de bien</h1></div>
      <div class="consulta-body">
        <p class="text-danger mb-3"><?= e($error) ?></p>
        <?php if ($codigo !== ''): ?>
          <p class="small text-secondary mb-0">Código buscado: <strong><?= e($codigo) ?></strong></p>
        <?php endif; ?>
      </div>
    </div>
  <?php elseif ($registro): ?>
    <div class="consulta-card">
      <div class="consulta-header">
        <div class="small opacity-75 mb-1">Trazabilidad de bienes</div>
        <div class="code-badge"><?= e($registro['codigo']) ?></div>
        <h1 class="h5 mt-3 mb-0"><?= e($registro['tipo_bien_nombre']) ?></h1>
      </div>
      <div class="consulta-body">
        <?php if (!empty($registro['fotos'])): ?>
        <div class="fotos-section mb-3 pb-0 border-0 mt-0 pt-0">
          <div class="small text-secondary mb-2">Fotografías</div>
          <div class="fotos-grid">
            <?php foreach ($registro['fotos'] as $i => $foto): ?>
              <button type="button" class="foto-item border-0 p-0" data-bs-toggle="modal" data-bs-target="#modalFoto" data-src="<?= e($foto['url_publica']) ?>" data-idx="<?= (int) $i ?>">
                <img src="<?= e($foto['url_publica']) ?>" alt="Foto <?= (int) $i + 1 ?>" loading="lazy">
              </button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="detail-row">
          <div class="detail-label">Municipio</div>
          <div class="detail-value"><?= e($registro['municipio_nombre']) ?></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Juzgado</div>
          <div class="detail-value"><?= e($registro['juzgado_nombre']) ?></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Responsable</div>
          <div class="detail-value"><?= e($registro['responsable_nombre']) ?></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Fecha</div>
          <div class="detail-value"><?= e(fmtFecha($registro['fecha_registro'])) ?></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Cantidad</div>
          <div class="detail-value"><?= (int) $registro['cantidad'] ?> <?= e($registro['tipo_bien_unidad'] ?? '') ?></div>
        </div>
        <?php if (!empty($registro['perifericos'])): ?>
        <div class="detail-row">
          <div class="detail-label">Periféricos</div>
          <div class="detail-value">
            <?= e(implode(', ', array_map(
                fn ($p) => $p['nombre'] . ' (' . $p['cantidad'] . ')',
                $registro['perifericos']
            ))) ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($registro['observaciones'])): ?>
        <div class="detail-row">
          <div class="detail-label">Observaciones</div>
          <div class="detail-value"><?= nl2br(e($registro['observaciones'])) ?></div>
        </div>
        <?php endif; ?>

        <div class="mt-4 pt-2">
          <div class="small text-secondary mb-2">Preparación del bien</div>
          <div class="d-flex flex-wrap gap-2">
            <?php
            $checks = [
                'Limpieza' => !empty($registro['limpieza']),
                'Embalado' => !empty($registro['embalado']),
                'Rotulado' => !empty($registro['rotulado']),
                'Foto' => !empty($registro['foto']),
            ];
            foreach ($checks as $label => $done):
            ?>
              <span class="check-pill <?= $done ? 'done' : 'pending' ?>">
                <?= $done ? '✓' : '○' ?> <?= e($label) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="consulta-card">
      <div class="consulta-header"><h1 class="h5 mb-0">Consulta de bien</h1></div>
      <div class="consulta-body">
        <p class="mb-0 text-secondary">Escanee el código QR del rótulo para ver los detalles del bien registrado.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade foto-modal" id="modalFoto" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Fotografía</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-2">
        <img src="" alt="Fotografía ampliada" id="modalFotoImg">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('modalFoto')?.addEventListener('show.bs.modal', (ev) => {
  const btn = ev.relatedTarget;
  if (!btn) return;
  const img = document.getElementById('modalFotoImg');
  if (img) img.src = btn.dataset.src || '';
});
</script>
</body>
</html>
