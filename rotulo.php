<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$codigo = trim($_GET['codigo'] ?? '');
$registro = null;
$error = '';

if ($codigo === '') {
    $error = 'Código de trazabilidad requerido.';
} else {
    try {
        $pdo = getPDO();
        $registro = obtenerRegistroPorCodigo($pdo, $codigo);
        if (!$registro) {
            $error = 'Registro no encontrado.';
        }
    } catch (Throwable $e) {
        $error = 'Error al cargar el registro.';
    }
}

function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
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

$urlConsulta = $registro ? urlConsultaPublica($registro['codigo']) : '';
$perifTexto = '';
if ($registro && !empty($registro['perifericos'])) {
    $perifTexto = implode(', ', array_map(
        fn ($p) => $p['nombre'] . ' (' . $p['cantidad'] . ')',
        $registro['perifericos']
    ));
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $registro ? 'Rótulo · ' . e($registro['codigo']) : 'Rótulo' ?></title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; font-family: Arial, Helvetica, sans-serif;
    background: #eef2f7; color: #0f172a;
  }
  .toolbar {
    padding: .75rem 1rem; background: #1f4e79; color: #fff;
    display: flex; gap: .75rem; align-items: center; justify-content: center;
  }
  .toolbar button {
    border: 0; background: #fff; color: #1f4e79; font-weight: 700;
    padding: .45rem 1rem; border-radius: 8px; cursor: pointer;
  }
  .rotulo-page {
    display: flex; justify-content: center; padding: 1.5rem;
  }
  .rotulo {
    width: 100mm; min-height: 62mm; background: #fff;
    border: 1px solid #cbd5e1; border-radius: 8px;
    padding: 8mm 7mm; box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
  }
  .rotulo-head {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 4mm; border-bottom: 1px solid #e2e8f0; padding-bottom: 4mm; margin-bottom: 4mm;
  }
  .rotulo-brand { font-size: 8pt; color: #64748b; text-transform: uppercase; letter-spacing: .08em; }
  .rotulo-codigo { font-size: 13pt; font-weight: 800; letter-spacing: .05em; margin-top: 1mm; }
  .rotulo-qr canvas, .rotulo-qr img { display: block; width: 24mm !important; height: 24mm !important; }
  .rotulo-tipo { font-size: 11pt; font-weight: 700; margin-bottom: 3mm; line-height: 1.25; }
  .rotulo-line { font-size: 8.5pt; line-height: 1.35; margin-bottom: 1.5mm; }
  .rotulo-line strong { color: #475569; font-weight: 600; }
  .rotulo-foot {
    margin-top: 4mm; padding-top: 3mm; border-top: 1px dashed #cbd5e1;
    font-size: 7pt; color: #64748b; text-align: center;
  }
  .error-box {
    max-width: 480px; margin: 2rem auto; background: #fff; border-radius: 10px;
    padding: 1.25rem; border: 1px solid #fecaca; color: #b91c1c;
  }
  @media print {
    body { background: #fff; }
    .toolbar { display: none !important; }
    .rotulo-page { padding: 0; }
    .rotulo { box-shadow: none; border: 1px solid #000; border-radius: 0; }
  }
  @page { size: 100mm 62mm; margin: 0; }
</style>
</head>
<body>
<?php if ($error): ?>
  <div class="error-box"><?= e($error) ?></div>
<?php else: ?>
  <div class="toolbar">
    <span>Rótulo de trazabilidad — <?= e($registro['codigo']) ?></span>
    <button type="button" onclick="window.print()">Imprimir</button>
  </div>
  <div class="rotulo-page">
    <div class="rotulo">
      <div class="rotulo-head">
        <div>
          <div class="rotulo-brand">Trazabilidad de bienes</div>
          <div class="rotulo-codigo"><?= e($registro['codigo']) ?></div>
        </div>
        <div class="rotulo-qr"><canvas id="qrCanvas" aria-hidden="true"></canvas></div>
      </div>
      <div class="rotulo-tipo"><?= e($registro['tipo_bien_nombre']) ?></div>
      <div class="rotulo-line"><strong>Municipio:</strong> <?= e($registro['municipio_nombre']) ?></div>
      <div class="rotulo-line"><strong>Juzgado:</strong> <?= e($registro['juzgado_nombre']) ?></div>
      <div class="rotulo-line"><strong>Responsable:</strong> <?= e($registro['responsable_nombre']) ?></div>
      <div class="rotulo-line"><strong>Fecha:</strong> <?= e(fmtFecha($registro['fecha_registro'])) ?> · <strong>Cant:</strong> <?= (int) $registro['cantidad'] ?> <?= e($registro['tipo_bien_unidad'] ?? '') ?></div>
      <?php if ($perifTexto !== ''): ?>
      <div class="rotulo-line"><strong>Periféricos:</strong> <?= e($perifTexto) ?></div>
      <?php endif; ?>
      <?php if (!empty($registro['observaciones'])): ?>
      <div class="rotulo-line"><strong>Notas:</strong> <?= e(mb_strimwidth($registro['observaciones'], 0, 120, '…')) ?></div>
      <?php endif; ?>
      <div class="rotulo-foot">Escanee el QR para consultar este bien</div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
  <script>
    QRCode.toCanvas(document.getElementById('qrCanvas'), <?= json_encode($urlConsulta, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, {
      width: 120,
      margin: 1,
      color: { dark: '#0f172a', light: '#ffffff' }
    });
  </script>
<?php endif; ?>
</body>
</html>
