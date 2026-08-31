<?php

function storageConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
    }
    return $cfg['storage'];
}

function extraerRutaRelativaFoto(string $ruta): string
{
    if (str_contains($ruta, 'foto.php?f=')) {
        parse_str(parse_url($ruta, PHP_URL_QUERY) ?? '', $qs);
        return ltrim((string) ($qs['f'] ?? ''), '/');
    }

    if (preg_match('#/object/(?:public/)?[^/]+/(.+)$#', $ruta, $coincidencia)) {
        return $coincidencia[1];
    }

    return ltrim($ruta, '/');
}

function urlFotoVisible(string $ruta): string
{
    $relativa = extraerRutaRelativaFoto($ruta);
    if ($relativa === '') {
        return '';
    }

    $s = storageConfig();
    if (!empty($s['bucket_public'])) {
        return rtrim($s['url'], '/') . '/object/public/' . $s['bucket'] . '/' . $relativa;
    }

    return 'api/foto.php?f=' . rawurlencode($relativa);
}

function headersSupabaseStorage(): array
{
    $serviceKey = trim((string) (storageConfig()['service_key'] ?? ''));

    if ($serviceKey === '' || $serviceKey === 'TU_SERVICE_ROLE_KEY') {
        throw new RuntimeException('Falta la clave secreta de Supabase en config.php.');
    }

    return [
        'apikey: ' . $serviceKey,
        'Authorization: Bearer ' . $serviceKey,
    ];
}

/**
 * Sirve una foto del bucket (útil cuando el bucket es privado).
 */
function streamFotoSupabase(string $rutaRelativa): void
{
    $rutaRelativa = extraerRutaRelativaFoto($rutaRelativa);
    if ($rutaRelativa === '' || str_contains($rutaRelativa, '..')) {
        http_response_code(404);
        exit;
    }

    $s = storageConfig();
    $url = rtrim($s['url'], '/') . '/object/' . $s['bucket'] . '/' . $rutaRelativa;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => headersSupabaseStorage(),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
    ]);

    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($errorCurl || $respuesta === false) {
        http_response_code(502);
        exit;
    }

    $headers = substr($respuesta, 0, $headerSize);
    $cuerpo = substr($respuesta, $headerSize);

    if ($codigo >= 300) {
        http_response_code($codigo === 404 ? 404 : 502);
        exit;
    }

    $contentType = 'image/jpeg';
    if (preg_match('/^Content-Type:\s*([^\r\n]+)/mi', $headers, $coincidencia)) {
        $contentType = trim($coincidencia[1]);
    }

    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=86400');
    echo $cuerpo;
}

/**
 * Sube una foto (dataURL base64) a Supabase Storage y devuelve la ruta relativa.
 */
function subirFotoSupabase(string $base64Data, string $nombreArchivo): string
{
    $s = storageConfig();
    $serviceKey = trim((string) ($s['service_key'] ?? ''));

    if ($serviceKey === '' || $serviceKey === 'TU_SERVICE_ROLE_KEY') {
        throw new RuntimeException(
            'Falta la clave secreta. En Supabase → Settings → API → Secret keys, '
            . 'copia la clave default (sb_secret_...). No uses la Publishable.'
        );
    }

    if (strpos($base64Data, ',') !== false) {
        [, $base64Data] = explode(',', $base64Data, 2);
    }
    $binario = base64_decode($base64Data);
    if ($binario === false) {
        throw new RuntimeException('Foto inválida.');
    }

    $ruta = date('Y/m/') . $nombreArchivo;
    $url = rtrim($s['url'], '/') . '/object/' . $s['bucket'] . '/' . $ruta;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $binario,
        CURLOPT_HTTPHEADER => array_merge(headersSupabaseStorage(), [
            'Content-Type: image/jpeg',
            'x-upsert: true',
        ]),
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    if ($errorCurl) {
        throw new RuntimeException('Error de red subiendo foto: ' . $errorCurl);
    }
    if ($codigo >= 300) {
        throw new RuntimeException('Supabase Storage respondió ' . $codigo . ': ' . $respuesta);
    }

    return $ruta;
}
