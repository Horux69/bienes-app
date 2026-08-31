<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

try {
    if ($metodo === 'GET' && $accion === 'setup') {
        try {
            $pdo = getPDO();
            $total = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
            responderJsonAuth(200, ['ok' => true, 'needs_setup' => $total === 0]);
        } catch (Throwable $e) {
            responderJsonAuth(500, [
                'ok' => false,
                'error' => 'La tabla usuarios no existe. Ejecute migrations/001_usuarios.sql en Supabase.',
            ]);
        }
    }

    if ($metodo === 'GET' && $accion === 'me') {
        $usuario = usuarioActual();
        if ($usuario === null) {
            responderJsonAuth(401, ['ok' => false, 'error' => 'No autenticado.']);
        }

        responderJsonAuth(200, ['ok' => true, 'usuario' => $usuario]);
    }

    if ($metodo === 'POST' && $accion === 'login') {
        $data = leerJsonBody();
        $email = strtolower(trim($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            responderJsonAuth(422, ['ok' => false, 'error' => 'Email y contraseña son obligatorios.']);
        }

        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('
                SELECT id, nombre, email, password_hash, rol, activo
                FROM usuarios
                WHERE email = ?
                LIMIT 1
            ');
            $stmt->execute([$email]);
            $fila = $stmt->fetch();
        } catch (Throwable $e) {
            responderJsonAuth(500, [
                'ok' => false,
                'error' => 'La tabla usuarios no existe. Abra install.php para crearla.',
            ]);
        }

        if (!$fila || !(bool) $fila['activo'] || !verificarPassword($password, $fila['password_hash'])) {
            responderJsonAuth(401, ['ok' => false, 'error' => 'Credenciales incorrectas.']);
        }

        establecerSesionUsuario($fila);
        responderJsonAuth(200, [
            'ok' => true,
            'usuario' => usuarioPublico($fila),
            'mensaje' => 'Sesión iniciada correctamente.',
        ]);
    }

    if ($metodo === 'POST' && $accion === 'logout') {
        cerrarSesion();
        responderJsonAuth(200, ['ok' => true, 'mensaje' => 'Sesión cerrada.']);
    }

    responderJsonAuth(405, ['ok' => false, 'error' => 'Acción no válida.']);
} catch (Throwable $e) {
    responderJsonAuth(500, ['ok' => false, 'error' => 'Error interno del servidor.']);
}
