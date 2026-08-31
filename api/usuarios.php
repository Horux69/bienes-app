<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

$metodo = $_SERVER['REQUEST_METHOD'];

function validarPasswordNueva(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }

    return null;
}

function validarDatosUsuario(array $data, bool $esCreacion): array
{
    $errores = [];
    $nombre = trim($data['nombre'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $rol = trim($data['rol'] ?? 'operario');

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no es válido.';
    }

    if (!in_array($rol, ['admin', 'operario'], true)) {
        $errores[] = 'El rol debe ser admin u operario.';
    }

    if ($esCreacion) {
        $password = (string) ($data['password'] ?? '');
        $errorPassword = validarPasswordNueva($password);
        if ($errorPassword) {
            $errores[] = $errorPassword;
        }
    } elseif (!empty($data['password'])) {
        $errorPassword = validarPasswordNueva((string) $data['password']);
        if ($errorPassword) {
            $errores[] = $errorPassword;
        }
    }

    return [
        'errores' => $errores,
        'nombre' => $nombre,
        'email' => $email,
        'rol' => $rol,
        'password' => (string) ($data['password'] ?? ''),
        'activo' => array_key_exists('activo', $data) ? (bool) $data['activo'] : true,
    ];
}

try {
    $pdo = getPDO();

    if ($metodo === 'GET') {
        $usuarioSesion = requireAuth();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id > 0) {
            if ($usuarioSesion['rol'] !== 'admin' && $usuarioSesion['id'] !== $id) {
                responderJsonAuth(403, ['ok' => false, 'error' => 'No tiene permiso para ver este usuario.']);
            }

            $stmt = $pdo->prepare('SELECT id, nombre, email, rol, activo, created_at, updated_at FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $fila = $stmt->fetch();

            if (!$fila) {
                responderJsonAuth(404, ['ok' => false, 'error' => 'Usuario no encontrado.']);
            }

            responderJsonAuth(200, ['ok' => true, 'usuario' => usuarioPublico($fila)]);
        }

        requireAdmin();
        $stmt = $pdo->query('
            SELECT id, nombre, email, rol, activo, created_at, updated_at
            FROM usuarios
            ORDER BY nombre
        ');
        $usuarios = array_map('usuarioPublico', $stmt->fetchAll());
        responderJsonAuth(200, ['ok' => true, 'usuarios' => $usuarios]);
    }

    if ($metodo === 'POST') {
        $total = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        if ($total === 0) {
            $datos = validarDatosUsuario(leerJsonBody(), true);
            if ($datos['errores']) {
                responderJsonAuth(422, ['ok' => false, 'error' => implode(' ', $datos['errores'])]);
            }

            $datos['rol'] = 'admin';

            $stmt = $pdo->prepare('
                INSERT INTO usuarios (nombre, email, password_hash, rol)
                VALUES (?, ?, ?, ?)
                RETURNING id, nombre, email, rol, activo, created_at, updated_at
            ');
            $stmt->execute([
                $datos['nombre'],
                $datos['email'],
                hashPassword($datos['password']),
                $datos['rol'],
            ]);

            $nuevo = $stmt->fetch();
            establecerSesionUsuario($nuevo);
            responderJsonAuth(201, [
                'ok' => true,
                'usuario' => usuarioPublico($nuevo),
                'mensaje' => 'Administrador inicial creado correctamente.',
            ]);
        }

        requireAdmin();
        $datos = validarDatosUsuario(leerJsonBody(), true);
        if ($datos['errores']) {
            responderJsonAuth(422, ['ok' => false, 'error' => implode(' ', $datos['errores'])]);
        }

        $stmt = $pdo->prepare('
            INSERT INTO usuarios (nombre, email, password_hash, rol)
            VALUES (?, ?, ?, ?)
            RETURNING id, nombre, email, rol, activo, created_at, updated_at
        ');
        $stmt->execute([
            $datos['nombre'],
            $datos['email'],
            hashPassword($datos['password']),
            $datos['rol'],
        ]);

        responderJsonAuth(201, [
            'ok' => true,
            'usuario' => usuarioPublico($stmt->fetch()),
            'mensaje' => 'Usuario creado correctamente.',
        ]);
    }

    if ($metodo === 'PUT') {
        $usuarioSesion = requireAuth();
        $data = leerJsonBody();
        $id = (int) ($data['id'] ?? 0);

        if ($id <= 0) {
            responderJsonAuth(400, ['ok' => false, 'error' => 'ID requerido.']);
        }

        $esAdmin = $usuarioSesion['rol'] === 'admin';
        $esPropio = $usuarioSesion['id'] === $id;

        if (!$esAdmin && !$esPropio) {
            responderJsonAuth(403, ['ok' => false, 'error' => 'No tiene permiso para editar este usuario.']);
        }

        $stmt = $pdo->prepare('SELECT id, nombre, email, rol, activo FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $actual = $stmt->fetch();

        if (!$actual) {
            responderJsonAuth(404, ['ok' => false, 'error' => 'Usuario no encontrado.']);
        }

        if ($esPropio) {
            $nombre = trim($data['nombre'] ?? $actual['nombre']);
            $password = (string) ($data['password'] ?? '');
            $passwordActual = (string) ($data['password_actual'] ?? '');

            if ($nombre === '') {
                responderJsonAuth(422, ['ok' => false, 'error' => 'El nombre es obligatorio.']);
            }

            if ($password !== '') {
                $errorPassword = validarPasswordNueva($password);
                if ($errorPassword) {
                    responderJsonAuth(422, ['ok' => false, 'error' => $errorPassword]);
                }

                $stmtHash = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
                $stmtHash->execute([$id]);
                $hashActual = $stmtHash->fetchColumn();

                if ($passwordActual === '' || !verificarPassword($passwordActual, (string) $hashActual)) {
                    responderJsonAuth(422, ['ok' => false, 'error' => 'La contraseña actual no es correcta.']);
                }
            }

            if ($password !== '') {
                $stmt = $pdo->prepare('
                    UPDATE usuarios SET nombre = ?, password_hash = ?, updated_at = now()
                    WHERE id = ?
                    RETURNING id, nombre, email, rol, activo, created_at, updated_at
                ');
                $stmt->execute([$nombre, hashPassword($password), $id]);
            } else {
                $stmt = $pdo->prepare('
                    UPDATE usuarios SET nombre = ?, updated_at = now()
                    WHERE id = ?
                    RETURNING id, nombre, email, rol, activo, created_at, updated_at
                ');
                $stmt->execute([$nombre, $id]);
            }

            $actualizado = $stmt->fetch();
            if ($esPropio) {
                establecerSesionUsuario($actualizado);
            }

            responderJsonAuth(200, [
                'ok' => true,
                'usuario' => usuarioPublico($actualizado),
                'mensaje' => 'Perfil actualizado correctamente.',
            ]);
        }

        $datos = validarDatosUsuario($data, false);
        if ($datos['errores']) {
            responderJsonAuth(422, ['ok' => false, 'error' => implode(' ', $datos['errores'])]);
        }

        if ($id === $usuarioSesion['id'] && !$datos['activo']) {
            responderJsonAuth(422, ['ok' => false, 'error' => 'No puede desactivar su propia cuenta.']);
        }

        if ($datos['password'] !== '') {
            $stmt = $pdo->prepare('
                UPDATE usuarios
                SET nombre = ?, email = ?, rol = ?, activo = ?, password_hash = ?, updated_at = now()
                WHERE id = ?
                RETURNING id, nombre, email, rol, activo, created_at, updated_at
            ');
            $stmt->execute([
                $datos['nombre'],
                $datos['email'],
                $datos['rol'],
                $datos['activo'],
                hashPassword($datos['password']),
                $id,
            ]);
        } else {
            $stmt = $pdo->prepare('
                UPDATE usuarios
                SET nombre = ?, email = ?, rol = ?, activo = ?, updated_at = now()
                WHERE id = ?
                RETURNING id, nombre, email, rol, activo, created_at, updated_at
            ');
            $stmt->execute([
                $datos['nombre'],
                $datos['email'],
                $datos['rol'],
                $datos['activo'],
                $id,
            ]);
        }

        responderJsonAuth(200, [
            'ok' => true,
            'usuario' => usuarioPublico($stmt->fetch()),
            'mensaje' => 'Usuario actualizado correctamente.',
        ]);
    }

    if ($metodo === 'DELETE') {
        $admin = requireAdmin();
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            responderJsonAuth(400, ['ok' => false, 'error' => 'ID requerido.']);
        }

        if ($id === $admin['id']) {
            responderJsonAuth(422, ['ok' => false, 'error' => 'No puede desactivar su propia cuenta.']);
        }

        $stmt = $pdo->prepare('
            UPDATE usuarios SET activo = false, updated_at = now()
            WHERE id = ?
            RETURNING id
        ');
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            responderJsonAuth(404, ['ok' => false, 'error' => 'Usuario no encontrado.']);
        }

        responderJsonAuth(200, ['ok' => true, 'mensaje' => 'Usuario desactivado correctamente.']);
    }

    responderJsonAuth(405, ['ok' => false, 'error' => 'Método no permitido.']);
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')) {
        responderJsonAuth(422, ['ok' => false, 'error' => 'Ya existe un usuario con ese email.']);
    }
    responderJsonAuth(500, ['ok' => false, 'error' => 'Error de base de datos.']);
} catch (Throwable $e) {
    responderJsonAuth(500, ['ok' => false, 'error' => 'Error interno del servidor.']);
}
