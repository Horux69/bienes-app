<?php
// Credenciales: .env (Easypanel/Docker), variables de entorno, o config.local.php (local).

function cargarArchivoEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lineas = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lineas === false) {
        return;
    }

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        if (!str_contains($linea, '=')) {
            continue;
        }

        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor, " \t\n\r\0\x0B\"'");

        if ($clave === '') {
            continue;
        }

        // No sobrescribir variables ya definidas en el sistema.
        if (getenv($clave) !== false) {
            continue;
        }

        putenv("{$clave}={$valor}");
        $_ENV[$clave] = $valor;
    }
}

function envVar(string $clave, string $default = ''): string
{
    $valor = getenv($clave);
    if ($valor !== false && $valor !== '') {
        return $valor;
    }
    if (isset($_ENV[$clave]) && $_ENV[$clave] !== '') {
        return (string) $_ENV[$clave];
    }
    return $default;
}

cargarArchivoEnv(__DIR__ . '/.env');

$cfg = [
    'db' => [
        'host' => envVar('SUPABASE_DB_HOST'),
        'port' => envVar('SUPABASE_DB_PORT', '5432'),
        'dbname' => envVar('SUPABASE_DB_NAME', 'postgres'),
        'user' => envVar('SUPABASE_DB_USER', 'postgres'),
        'password' => envVar('SUPABASE_DB_PASSWORD'),
    ],
    'storage' => [
        'url' => envVar('SUPABASE_URL') !== ''
            ? rtrim(envVar('SUPABASE_URL'), '/') . '/storage/v1'
            : '',
        'bucket' => envVar('SUPABASE_BUCKET', 'BIENES'),
        'bucket_public' => filter_var(envVar('SUPABASE_BUCKET_PUBLIC', 'false'), FILTER_VALIDATE_BOOLEAN),
        'service_key' => envVar('SUPABASE_SERVICE_KEY'),
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $cfg = array_replace_recursive($cfg, require $local);
}

return $cfg;
