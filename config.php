<?php
// Credenciales vía variables de entorno (producción) o config.local.php (desarrollo local).
// Copia config.local.php.example → config.local.php y completa los valores.

$cfg = [
    'db' => [
        'host' => getenv('SUPABASE_DB_HOST') ?: '',
        'port' => getenv('SUPABASE_DB_PORT') ?: '5432',
        'dbname' => getenv('SUPABASE_DB_NAME') ?: 'postgres',
        'user' => getenv('SUPABASE_DB_USER') ?: 'postgres',
        'password' => getenv('SUPABASE_DB_PASSWORD') ?: '',
    ],
    'storage' => [
        'url' => getenv('SUPABASE_URL')
            ? rtrim(getenv('SUPABASE_URL'), '/') . '/storage/v1'
            : '',
        'bucket' => getenv('SUPABASE_BUCKET') ?: 'BIENES',
        'bucket_public' => filter_var(getenv('SUPABASE_BUCKET_PUBLIC') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'service_key' => getenv('SUPABASE_SERVICE_KEY') ?: '',
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $cfg = array_replace_recursive($cfg, require $local);
}

return $cfg;
