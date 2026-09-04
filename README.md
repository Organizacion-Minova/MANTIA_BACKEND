# MANTIA — Backend

Sistema web de gestión de inventario y mantenimiento para elementos utilizados en entornos mineros: máquinas, equipos, herramientas, ubicaciones, usuarios, alertas, uso diario e inspecciones.

## Tecnologías

- **Laravel 13** (PHP 8.3) — Framework backend
- **Laravel Sanctum** — Autenticación API (tokens SPA + sesiones)
- **Spatie Laravel Permission** — Gestión de roles y permisos
- **Tailwind CSS 4** — Estilos
- **Vite** — Bundler de assets
- **PostgreSQL** (default) — Base de datos (soporta también SQLite, MySQL, MariaDB, SQL Server)
- **Redis** — Cache y colas
- **Laravel Queues** — Procesamiento de jobs en segundo plano

## Instalación

### Requisitos previos

- PHP 8.3 o superior
- Composer
- Node.js 18+ y npm
- PostgreSQL 14+ (o el gestor de base de datos que prefieras)

### 1. Clonar el repositorio

```bash
git clone https://github.com/Organizacion-Minova/MANTIA_BACKEND.git
cd MANTIA_BACKEND
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Instalar dependencias frontend (Node)

```bash
npm install
```

### 4. Configurar el archivo `.env`

El archivo `.env` está en `.gitignore` y **nunca debe subirse al repositorio**. Copia el archivo de ejemplo y configura tus credenciales:

```bash
cp .env.example .env
```

Edita el `.env` con tus datos. Variables clave:

```env
APP_NAME=MANTIA
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=Mantia_db
DB_USERNAME=postgres
DB_PASSWORD=cambia_esto_por_tu_contraseña

SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
SESSION_DRIVER=database
SESSION_LIFETIME=120

QUEUE_CONNECTION=database
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
```

> ⚠️ **Seguridad:** Nunca commits tu `.env` con credenciales reales. Si necesitas compartir configuración, usa `.env.example` como referencia.

### 5. Generar la clave de la aplicación y ejecutar migraciones

```bash
php artisan key:generate
php artisan migrate:fresh --seed
```

Esto creará todas las tablas necesarias (usuarios, permisos, roles, tablas de negocio como `company`, `machine`, `equipment`, `tool`, `location`, `inspection`, `gas_measurement`, etc.) y sembrará datos iniciales.

### 6. Ejecutar el servidor de desarrollo

```bash
composer dev
```

Este comando ejecuta simultáneamente:
- Servidor Laravel (`php artisan serve`)
- Listener de colas (`php artisan queue:listen`)
- Pail (logs en consola)
- Vite dev server (`npm run dev`)

O manualmente:

```bash
# Terminal 1: Servidor PHP
php artisan serve

# Terminal 2: Colas
php artisan queue:listen

# Terminal 3: Vite
npm run dev
```

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php
│   │   └── DevAuthController.php       # Login rápido por letra (solo local)
├── Models/
│   └── User.php
├── Providers/
├── routes/
│   ├── api.php                         # Rutas de la API
│   ├── web.php
│   └── console.php
database/
├── migrations/                         # Migraciones del sistema
├── seeders/                            # Seeders de datos
└── factories/
resources/
├── css/
├── js/
└── views/
config/
├── auth.php                            # Configuración de autenticación
├── sanctum.php                         # Configuración de Sanctum
├── permission.php                      # Configuración de Spatie Permission
├── database.php                        # Conexiones de base de datos
├── queue.php                           # Configuración de colas
└── ...
```

## API Endpoints principales

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/login` | Iniciar sesión con email y contraseña |
| `POST` | `/api/logout` | Cerrar sesión |
| `GET` | `/api/user` | Obtener usuario autenticado |
| `POST` | `/api/dev-login/{letra}` | Login rápido por letra (solo local) |
| `GET` | `/api/machine-categories` | Listar categorías de máquinas |
| `POST` | `/api/machine-categories` | Crear categoría de máquina |
| `PUT` | `/api/machine-categories/{id}` | Actualizar categoría de máquina |
| `DELETE` | `/api/machine-categories/{id}` | Eliminar categoría de máquina |

## Autenticación

El sistema utiliza **Laravel Sanctum** para la autenticación. El frontend (React en `localhost:5173`) envía las credenciales y recibe una cookie de sesión con estado (stateful). Para proteger rutas se usa el middleware `auth:sanctum`.

### Login rápido (entorno local)

Para desarrollo, existe un login por letra que accede directamente sin contraseña:

```
POST /api/dev-login/D  → Usuario Daniela
POST /api/dev-login/S  → Usuario Santiago
POST /api/dev-login/J  → Usuario Joan
POST /api/dev-login/L  → Usuario Leonardo
POST /api/dev-login/K  → Usuario Kevin
```

## Base de datos

### Tablas principales del sistema

- `users` — Usuarios del sistema
- `company` — Empresas/compañías
- `category_group` / `category` — Grupos y categorías de elementos
- `machine_category` — Categorías de máquinas
- `location_category` / `location` — Categorías y ubicaciones
- `equipment` — Equipos
- `machine` — Máquinas
- `tool` — Herramientas
- `inspection` — Inspecciones
- `gas_measurement` / `gas_measurement_location` — Mediciones de gases
- `personal_access_tokens` — Tokens Sanctum
- `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions` — Roles y permisos (Spatie)
- `jobs`, `failed_jobs`, `job_batches` — Colas

## Seeders

- `UserSeeder` — Crea un usuario de prueba (`test@mantia.com` / `password123`)
- `DevUsersSeeder` — Crea 5 usuarios de desarrollo con login por letra
- `CategoryMachineSeeder` — Categorías de máquinas (referenciado, pendiente de implementación)

## Testing

```bash
php artisan test
```

## Seguridad

- El archivo `.env` contiene credenciales sensibles (contraseñas de base de datos, claves API, credenciales de correo). Está en `.gitignore` y **nunca debe subirse al repositorio**.
- Usa `.env.example` como plantilla para nuevos entornos.
- El archivo `.env` real contiene credenciales locales (DB password, mail password, APP_KEY) que son específicas de cada desarrollador.
- En producción, cambia las credenciales por defecto y genera una nueva `APP_KEY` con `php artisan key:generate`.
- El login rápido por letra (`/api/dev-login/{letra}`) solo funciona en entorno `local`.

## Licencia

MIT
