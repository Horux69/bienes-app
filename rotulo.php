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
$qrDataUri = $registro ? generarQrDataUri($urlConsulta, 3) : '';
$perifTexto = '';
if ($registro && !empty($registro['perifericos'])) {
    $perifTexto = implode(', ', array_map(
        fn ($p) => $p['nombre'] . ' (' . $p['cantidad'] . ')',
        $registro['perifericos']
    ));
    if (mb_strlen($perifTexto) > 90) {
        $perifTexto = mb_strimwidth($perifTexto, 0, 90, '…');
    }
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
    flex-wrap: wrap;
  }
  .toolbar button {
    border: 0; background: #fff; color: #1f4e79; font-weight: 700;
    padding: .45rem 1rem; border-radius: 8px; cursor: pointer;
  }
  .rotulo-page {
    display: flex; justify-content: center; padding: 1.5rem;
  }
  .rotulo {
    width: 100mm; background: #fff;
    border: 1px solid #cbd5e1; border-radius: 8px;
    padding: 6mm 5mm; box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
  }
  .rotulo-head {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 3mm; border-bottom: 1px solid #e2e8f0; padding-bottom: 3mm; margin-bottom: 3mm;
  }
  .rotulo-brand { font-size: 7pt; color: #64748b; text-transform: uppercase; letter-spacing: .08em; }
  .rotulo-codigo { font-size: 12pt; font-weight: 800; letter-spacing: .05em; margin-top: 1mm; line-height: 1.1; }
  .rotulo-qr {
    flex-shrink: 0;
    width: 22mm;
    height: 22mm;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    border-radius: 3px;
    padding: 1mm;
    background: #fff;
  }
  .rotulo-qr img {
    display: block;
    width: 20mm;
    height: 20mm;
    object-fit: contain;
  }
  .rotulo-qr-fallback {
    font-size: 6pt;
    color: #64748b;
    text-align: center;
    word-break: break-all;
    line-height: 1.2;
    padding: 2px;
  }
  .rotulo-tipo { font-size: 10pt; font-weight: 700; margin-bottom: 2mm; line-height: 1.2; }
  .rotulo-line { font-size: 7.5pt; line-height: 1.25; margin-bottom: 1mm; }
  .rotulo-line strong { color: #475569; font-weight: 600; }
  .rotulo-foot {
    margin-top: 3mm; padding-top: 2mm; border-top: 1px dashed #cbd5e1;
    font-size: 6.5pt; color: #64748b; text-align: center;
  }
  .rotulo-credit {
    margin-top: 2mm;
    font-size: 6pt;
    color: #94a3b8;
    text-align: center;
    font-weight: 600;
    letter-spacing: .02em;
  }
  .error-box {
    max-width: 480px; margin: 2rem auto; background: #fff; border-radius: 10px;
    padding: 1.25rem; border: 1px solid #fecaca; color: #b91c1c;
  }

  @media print {
    @page {
      size: 100mm 105mm;
      margin: 2mm;
    }
    html, body {
      width: 100mm;
      height: auto;
      margin: 0 !important;
      padding: 0 !important;
      background: #fff !important;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .toolbar { display: none !important; }
    .rotulo-page {
      padding: 0;
      margin: 0;
      display: block;
    }
    .rotulo {
      width: 100%;
      max-width: 96mm;
      margin: 0 auto;
      padding: 3mm 4mm;
      box-shadow: none;
      border: 1px solid #000;
      border-radius: 0;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .rotulo-head { padding-bottom: 2mm; margin-bottom: 2mm; }
    .rotulo-tipo { font-size: 9pt; margin-bottom: 1.5mm; }
    .rotulo-line { font-size: 7pt; margin-bottom: .8mm; }
    .rotulo-codigo { font-size: 10pt; }
    .rotulo-qr { width: 20mm; height: 20mm; border-color: #000; }
    .rotulo-qr img { width: 18mm; height: 18mm; }
    .rotulo-foot { margin-top: 2mm; padding-top: 1.5mm; font-size: 6pt; }
    .rotulo-credit { margin-top: 1.5mm; font-size: 5.5pt; color: #64748b; }
  }
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
        <div class="rotulo-qr" title="<?= e($urlConsulta) ?>">
          <?php if ($qrDataUri !== ''): ?>
            <img src="<?= e($qrDataUri) ?>" alt="QR <?= e($registro['codigo']) ?>" width="100" height="100">
          <?php else: ?>
            <div class="rotulo-qr-fallback">QR no disponible</div>
          <?php endif; ?>
        </div>
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
      <div class="rotulo-line"><strong>Notas:</strong> <?= e(mb_strimwidth($registro['observaciones'], 0, 80, '…')) ?></div>
      <?php endif; ?>
      <div class="rotulo-foot">Escanee el QR para consultar este bien</div>
      <div class="rotulo-credit">Desarrollado por SICOE</div>
    </div>
  </div>
<?php endif; ?>
</body>
</html>
