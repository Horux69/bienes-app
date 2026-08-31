<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

$mensaje = '';
$tipo = 'info';
$yaInstalado = false;

try {
    $pdo = getPDO();
    $pdo->query('SELECT 1 FROM usuarios LIMIT 1');
    $yaInstalado = true;
    $mensaje = 'La tabla usuarios ya existe. Puede ir al login.';
    $tipo = 'success';
} catch (Throwable $e) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        try {
            $sql = file_get_contents(__DIR__ . '/migrations/001_usuarios.sql');
            $pdo = getPDO();
            $pdo->exec($sql);
            header('Location: login.php?instalado=1');
            exit;
        } catch (Throwable $ex) {
            $mensaje = 'Error al instalar: ' . $ex->getMessage();
            $tipo = 'danger';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalar usuarios — Trazabilidad de Bienes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/app.css" rel="stylesheet">
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="eyebrow">Sistema de trazabilidad</div>
  <h1>Instalación de usuarios</h1>

  <?php if ($yaInstalado): ?>
    <div class="alert alert-success small mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <a href="login.php" class="btn btn-brand w-100">Ir al login</a>
  <?php else: ?>
    <p class="text-secondary small mb-3">
      Falta la tabla <code>usuarios</code> en Supabase. Este paso crea la tabla necesaria para el login.
    </p>
    <?php if ($mensaje): ?>
      <div class="alert alert-<?= htmlspecialchars($tipo) ?> small"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <form method="post">
      <button type="submit" class="btn btn-brand w-100">Crear tabla usuarios</button>
    </form>
    <p class="text-secondary small mt-3 mb-0">
      Alternativa manual: ejecute <code>migrations/001_usuarios.sql</code> en el SQL Editor de Supabase.
    </p>
  <?php endif; ?>
</div>
</body>
</html>
