<?php

require_once __DIR__ . '/../storage.php';
require_once __DIR__ . '/../auth.php';

requireAuth();

$f = $_GET['f'] ?? '';
if ($f === '') {
    http_response_code(400);
    exit;
}

streamFotoSupabase($f);
