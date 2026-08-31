<?php

declare(strict_types=1);

function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);

    session_start();
}

function usuarioActual(): ?array
{
    iniciarSesion();

    if (empty($_SESSION['usuario_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['usuario_id'],
        'nombre' => (string) ($_SESSION['usuario_nombre'] ?? ''),
        'email' => (string) ($_SESSION['usuario_email'] ?? ''),
        'rol' => (string) ($_SESSION['usuario_rol'] ?? 'operario'),
    ];
}

function establecerSesionUsuario(array $usuario): void
{
    iniciarSesion();
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nombre'] = (string) $usuario['nombre'];
    $_SESSION['usuario_email'] = (string) $usuario['email'];
    $_SESSION['usuario_rol'] = (string) $usuario['rol'];
}

function cerrarSesion(): void
{
    iniciarSesion();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verificarPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function responderJsonAuth(int $codigo, array $payload): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function requireAuth(): array
{
    $usuario = usuarioActual();
    if ($usuario === null) {
        responderJsonAuth(401, ['ok' => false, 'error' => 'Debe iniciar sesión.']);
    }

    return $usuario;
}

function requireAdmin(): array
{
    $usuario = requireAuth();
    if ($usuario['rol'] !== 'admin') {
        responderJsonAuth(403, ['ok' => false, 'error' => 'Solo el administrador puede realizar esta acción.']);
    }

    return $usuario;
}

function leerJsonBody(): array
{
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

function usuarioPublico(array $fila): array
{
    return [
        'id' => (int) $fila['id'],
        'nombre' => $fila['nombre'],
        'email' => $fila['email'],
        'rol' => $fila['rol'],
        'activo' => (bool) $fila['activo'],
        'created_at' => $fila['created_at'] ?? null,
        'updated_at' => $fila['updated_at'] ?? null,
    ];
}
