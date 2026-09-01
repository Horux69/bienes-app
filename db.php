<?php

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $cfg = require __DIR__ . '/config.php';
        $d = $cfg['db'];

        $dsn = "pgsql:host={$d['host']};port={$d['port']};dbname={$d['dbname']};sslmode=require";

        // Si el host tiene registro IPv4, forzar TCP por IPv4 (evita ENETUNREACH en VPS sin IPv6).
        if (!filter_var($d['host'], FILTER_VALIDATE_IP)) {
            $ipv4 = gethostbynamel($d['host']);
            if ($ipv4 && isset($ipv4[0])) {
                $dsn = "pgsql:host={$d['host']};hostaddr={$ipv4[0]};port={$d['port']};dbname={$d['dbname']};sslmode=require";
            }
        }

        try {
            $pdo = new PDO($dsn, $d['user'], $d['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Network is unreachable')
                || str_contains($e->getMessage(), '2600:')) {
                throw new PDOException(
                    'El servidor no tiene IPv6. Use el Connection Pooler de Supabase (session mode) en las variables de entorno.',
                    (int) $e->getCode(),
                    $e
                );
            }
            throw $e;
        }
    }
    return $pdo;
}

