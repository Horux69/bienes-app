# Guía de despliegue — BIENES_APP

Sistema de **Trazabilidad de Bienes** desplegado con **PHP 8.2**, **Supabase** (PostgreSQL + Storage) y **Hostinger VPS** con **Easypanel**.

---

## 1. Arquitectura general

```
Usuario (navegador)
        ↓
Hostinger VPS (Easypanel + Docker + PHP 8.2 + Apache)
        ↓
Supabase (PostgreSQL + Storage)
```

| Componente | Dónde vive | Función |
|------------|------------|---------|
| Frontend + API PHP | VPS Hostinger | Interfaz, login, CRUD, subida de fotos |
| Base de datos | Supabase PostgreSQL | Datos y catálogos |
| Fotos | Supabase Storage (bucket `BIENES`) | Almacenamiento de imágenes |
| Dominio público | Easypanel (`*.easypanel.host`) | Acceso HTTPS a la app |

---

## 2. Configuración en Supabase

### 2.1 Proyecto

1. Crear o usar un proyecto en [supabase.com](https://supabase.com).
2. Anotar el **Project ref** (ej. `tu-proyecto-ref`).

### 2.2 Base de datos — esquema inicial

En **SQL Editor**, ejecutar en este orden:

1. **`schema.sql`** — tablas principales (`municipios`, `juzgados`, `responsables`, `tipos_bienes`, `perifericos`, `registros_bienes`, etc.).
2. **`migrations/001_usuarios.sql`** — tabla `usuarios` para login y roles.

> Alternativa: visitar `install.php` una sola vez en producción (luego eliminarlo o protegerlo).

### 2.3 Storage — bucket de fotos

1. **Storage** → **New bucket**
2. Nombre: **`BIENES`**
3. Bucket **privado** (recomendado)
4. Las fotos se sirven vía proxy `api/foto.php`

### 2.4 Credenciales de API

**Project Settings → API:**

| Dato | Variable de entorno |
|------|---------------------|
| Project URL | `SUPABASE_URL` → `https://TU_REF.supabase.co` |
| Secret key (`sb_secret_...`) | `SUPABASE_SERVICE_KEY` |

> No usar la Publishable key en el backend.

**Project Settings → Database:**

| Dato | Variable |
|------|----------|
| Password | `SUPABASE_DB_PASSWORD` |
| Puerto | `SUPABASE_DB_PORT` → `5432` |
| Base de datos | `SUPABASE_DB_NAME` → `postgres` |

### 2.5 Connection Pooler (obligatorio en VPS sin IPv6)

El host directo `db.TU_REF.supabase.co` usa **IPv6**. Muchos VPS (incluido Hostinger) **no tienen IPv6**, lo que produce:

```
Network is unreachable
```

**Solución:** usar **Session pooler** (IPv4):

1. **Project Settings → Database → Connect**
2. Elegir **Session pooler** (puerto **5432**)
3. Configurar:

| Variable | Valor |
|----------|--------|
| `SUPABASE_DB_HOST` | `aws-0-REGION.pooler.supabase.com` |
| `SUPABASE_DB_PORT` | `5432` |
| `SUPABASE_DB_USER` | `postgres.TU_PROJECT_REF` |
| `SUPABASE_DB_PASSWORD` | contraseña de la BD |
| `SUPABASE_DB_NAME` | `postgres` |

> El usuario del pooler es `postgres.TU_REF`, **no** solo `postgres`.

### 2.6 Variables de entorno (plantilla)

```env
SUPABASE_DB_HOST=aws-0-REGION.pooler.supabase.com
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres.TU_PROJECT_REF
SUPABASE_DB_PASSWORD=tu_password
SUPABASE_URL=https://TU_REF.supabase.co
SUPABASE_SERVICE_KEY=sb_secret_...
SUPABASE_BUCKET=BIENES
SUPABASE_BUCKET_PUBLIC=false
```

---

## 3. Configuración en Hostinger

### 3.1 Plan utilizado

| Servicio | Uso |
|----------|-----|
| **VPS KVM** | Hosting de la aplicación |
| **Easypanel** | Panel (Ubuntu 24.04) |
| Hosting compartido | No necesario para esta app |

**Acceso SSH:** `ssh root@TU_IP_DEL_VPS`

### 3.2 Por qué VPS y no hosting compartido

La app requiere PHP con **`pdo_pgsql`**. En hosting compartido de Hostinger normalmente no está disponible. El VPS permite Docker + PHP + cliente PostgreSQL.

### 3.3 Acceso a Easypanel

```
http://TU_IP_DEL_VPS:3000
```

---

## 4. Despliegue en Easypanel

### 4.1 Crear proyecto y servicio

1. **+ Nuevo** → proyecto: `bienes-app`
2. **+ Servicio** → tipo: **App**
3. **Fuente:** GitHub
4. **Repositorio:** `usuario/bienes-app` (formato `owner/repo`, sin URL)
5. **Rama:** `master`
6. **Ruta de compilación:** `/`
7. **Build:** **Dockerfile** → archivo `Dockerfile`

### 4.2 Puerto

| Ajuste | Valor |
|--------|--------|
| Puerto interno | **80** |

### 4.3 Variables de entorno

En **Entorno**, pegar las 9 variables de la sección 2.6.

Recomendado:
- **Crear archivo env:** activado
- **Ruta:** `.env`

### 4.4 Dominio

Easypanel asigna un dominio automático, por ejemplo:

```
https://bienes-app-bienes-app.xxxxx.easypanel.host
```

**URLs útiles:**
- Login: `/login.php`
- App: `/index.php`
- Diagnóstico: `/api/health.php`
- Instalación (una vez): `/install.php`

### 4.5 Desplegar

1. **Guardar**
2. **Implementar**
3. Verificar logs: debe aparecer `FROM php:8.2-apache`
4. CPU/memoria deben dejar de estar en 0%

---

## 5. GitHub

### 5.1 Estructura de credenciales

| Archivo | En GitHub | Contenido |
|---------|-----------|-----------|
| `config.php` | Sí | Solo lectura de variables de entorno |
| `config.local.php` | No (`.gitignore`) | Credenciales para desarrollo local |
| `.env` | No | Credenciales en el servidor |

### 5.2 Archivos clave de despliegue

| Archivo | Función |
|---------|---------|
| `Dockerfile` | PHP 8.2 + Apache + `pdo_pgsql` |
| `config.php` | Lee `.env` y variables de entorno |
| `.env.example` | Plantilla de variables |
| `api/health.php` | Diagnóstico de conexión |
| `db.php` | Conexión PDO a Supabase |

### 5.3 Actualizar producción

```powershell
cd C:\laragon\www\BIENES_APP
git add .
git commit -m "Descripción del cambio"
git push origin master
```

Luego en Easypanel → **Implementar**.

---

## 6. Desarrollo local (Laragon)

### 6.1 Requisitos

- PHP 8.2 con `pdo_pgsql` y `curl`
- Proyecto en `C:\laragon\www\BIENES_APP`

### 6.2 Credenciales locales

```bash
cp config.local.php.example config.local.php
```

Completar credenciales en `config.local.php` (no se sube a GitHub).

En local se puede usar el host directo `db.TU_REF.supabase.co`.

### 6.3 Acceso

- Virtual host: `http://bienes_app.test/`
- O IP de red: `http://192.168.x.x/BIENES_APP/`

---

## 7. Usuarios y roles

| Rol | Permisos |
|-----|----------|
| **admin** | Registrar, listado, perfil, catálogos, usuarios |
| **operario** | Registrar, listado, perfil |

Si no hay usuarios, `login.php` permite crear el administrador inicial.

---

## 8. Problemas frecuentes

| Problema | Causa | Solución |
|----------|--------|----------|
| GitHub rechaza push | Secret key en código | Quitar secretos de `config.php` |
| `src refspec main does not match` | Rama es `master` | `git push origin master` |
| Easypanel: `open code: no such file` | Dockerfile mal configurado | Archivo: `Dockerfile` |
| "Service is not started" | Contenedor apagado | **Implementar** / Play |
| "Tabla usuarios no existe" | Error de conexión BD | Revisar variables / pooler |
| `Network is unreachable` | VPS sin IPv6 | Session pooler + `postgres.ref` |
| Fotos no se ven | Bucket privado | Proxy `api/foto.php` |
| `env_file: no` | Vars del contenedor | OK si health.php muestra `ok: true` |

---

## 9. Comandos útiles

### VPS (SSH)

```bash
docker ps | grep bienes
docker logs NOMBRE_CONTENEDOR --tail 50
```

### PC (Git)

```powershell
git status
git push origin master
```

---

## 10. Seguridad

1. Rotar claves de Supabase si estuvieron expuestas.
2. Eliminar o proteger `install.php` en producción.
3. No subir `config.local.php` ni `.env` a GitHub.
4. Usar HTTPS (Easypanel + Let's Encrypt).
5. Limitar usuarios con rol `admin`.

---

## 11. Checklist de producción

- [ ] `schema.sql` ejecutado en Supabase
- [ ] `migrations/001_usuarios.sql` ejecutado
- [ ] Bucket `BIENES` creado en Storage
- [ ] Variables en Easypanel (pooler IPv4)
- [ ] Deploy exitoso en Easypanel
- [ ] `/api/health.php` responde `ok: true`
- [ ] Login y registro de bienes funcionan
- [ ] `install.php` eliminado o protegido
- [ ] Claves rotadas si hubo exposición

---

## 12. Dominio propio (opcional)

1. **Hostinger → Dominios → DNS** → registro **A** → IP del VPS
2. **Easypanel → Dominios** → agregar subdominio + HTTPS

---

## 13. Resumen

```
Laragon (dev) + GitHub (código) → Easypanel en VPS (PHP) → Supabase Pooler (datos) + Storage (fotos)
```
