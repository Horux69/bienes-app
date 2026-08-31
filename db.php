<?php

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $cfg = require __DIR__ . '/config.php';
        $d = $cfg['db'];
        $dsn = "pgsql:host={$d['host']};port={$d['port']};dbname={$d['dbname']};sslmode=require";
        $pdo = new PDO($dsn, $d['user'], $d['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
