![Vista previa de MANTIA](/src/assets/img/logo%20-%20Mantia_readme.png)

MANTIA — Backend

Sistema web de gestión de inventario y mantenimiento para elementos utilizados en entornos mineros: máquinas, equipos, herramientas, ubicaciones, usuarios, alertas e inspecciones.

Este repositorio contiene la API (Laravel). El frontend (React + Vite) vive en un repositorio aparte y corre en http://localhost:5173.

Tecnologías
Laravel 13 (PHP 8.3+)
Laravel Sanctum — autenticación SPA con cookies de sesión
Spatie Laravel Permission — roles y permisos
PostgreSQL 14+ — base de datos
Colas y caché en base de datos (QUEUE_CONNECTION=database, CACHE_STORE=database)
Vite 
Instalación
Requisitos previos
PHP 8.3 o superior
Composer
Node.js 18+ y npm
PostgreSQL 14+
1. Clonar el repositorio
bash
git clone https://github.com/Organizacion-Minova/MANTIA_BACKEND.git
cd MANTIA_BACKEND
2. Instalar dependencias
bash
composer install
npm install
3. Configurar el .env
bash
cp .env.example .env

Abre el .env y completa estos datos:

env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=Mantia_db
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña

SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost

MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=tu_contraseña_de_aplicacion

Crea antes la base de datos Mantia_db en PostgreSQL.

El correo se usa para las notificaciones de cuentas y la recuperación de contraseña. Con Gmail necesitas una contraseña de aplicación, no tu contraseña normal.

4. Generar la clave y crear la base de datos
bash
php artisan key:generate
php artisan migrate:fresh --seed
5. Ejecutar el proyecto
bash
composer dev

Este comando levanta a la vez el servidor Laravel, el listener de colas, los logs (Pail) y Vite.

O manualmente, en tres terminales:

bash
php artisan serve
php artisan queue:listen
npm run dev

La API queda en http://localhost:8000.

Estructura del proyecto
app/
├── Http/Controllers/      # Controladores de la API
├── Listeners/             # Registro de inicio y cierre de sesión
├── Models/                # Modelos Eloquent
├── Notifications/         # Correos y alertas del sistema
├── Observers/             # Generan alertas al crear, editar o eliminar
├── Providers/
└── Services/              # AlertaService (envío de alertas)
config/
├── alertas.php            # Límites de gases y stock mínimo
├── cors.php, sanctum.php, permission.php, ...
database/
├── migrations/
└── seeders/
routes/
├── api.php                # Rutas de la API
└── web.php
Roles y estados de usuario

Roles (se crean con RoleAndAdminSeeder):

Administrador
Tecnico
Recepcionista

Estados de usuario: pending (recién registrado), approved y rejected. Un usuario nuevo queda en pending hasta que un administrador lo apruebe; mientras tanto no puede iniciar sesión.

API Endpoints

Todas las rutas empiezan con /api.

Autenticación (públicas)
Método	Ruta	Descripción
POST	/register	Registrar usuario (queda pendiente de aprobación)
POST	/login	Iniciar sesión con correo y contraseña
POST	/logout	Cerrar sesión
POST	/forgot-password	Envía un código de 6 dígitos al correo
POST	/reset-password	Cambia la contraseña usando el código
POST	/dev-login/{letra}	Login rápido por letra (solo entorno local)
Usuario autenticado (auth:sanctum)
Método	Ruta	Descripción
GET	/user	Usuario actual con sus roles
Administración (rol Administrador)
Método	Ruta	Descripción
GET	/admin/requests	Solicitudes de cuenta pendientes
POST	/admin/approve/{id}	Aprobar solicitud
POST	/admin/reject/{id}	Rechazar solicitud
Recursos (requieren auth:sanctum)

Cada recurso tiene GET (listar), POST (crear), PUT /{id} (actualizar) y DELETE /{id} (eliminar).

Ruta base	Recurso
/machine-categories	Categorías de máquinas
/company	Empresas
/location-category	Categorías de ubicación
/location	Ubicaciones
/category-tools	Categorías de herramientas (además tiene GET /{id})
/tools	Herramientas
Autenticación

Se usa Laravel Sanctum en modo SPA: el frontend (localhost:5173) pide la cookie CSRF en /sanctum/csrf-cookie, inicia sesión y el navegador guarda la cookie de sesión. Las rutas protegidas usan el middleware auth:sanctum.

Para que funcione en local, SANCTUM_STATEFUL_DOMAINS y SESSION_DOMAIN deben coincidir con el dominio del frontend (ver .env), y config/cors.php debe permitir ese origen.

Login rápido (solo desarrollo)

Entra sin contraseña usando la letra de un usuario de desarrollo:

POST /api/dev-login/D  → Daniela
POST /api/dev-login/S  → Santiago
POST /api/dev-login/J  → Joan
POST /api/dev-login/L  → Leonardo
POST /api/dev-login/K  → Kevin

Solo existe cuando APP_ENV=local. Nunca lo dejes activo en producción.

Alertas

Los observers de app/Observers crean alertas automáticamente cuando se registra, cambia de estado o se elimina un elemento (empresas, categorías, ubicaciones, máquinas, equipos, herramientas, inspecciones y mediciones de gases).

Las alertas se guardan en la tabla notifications (canal database).
Cada alerta tiene un nivel: info, warning o danger.
Las mediciones de gases se comparan con los límites de config/alertas.php (oxígeno, metano, CO₂, H₂S, CO y NO₂). Si algún valor está fuera de rango, la alerta sale como danger.
config/alertas.php también define el stock mínimo de herramientas.
Base de datos
Grupo	Tablas
Usuarios y sesión	users, password_reset_tokens, sessions, personal_access_tokens
Roles y permisos (Spatie)	permissions, roles, model_has_permissions, model_has_roles, role_has_permissions
Empresas y categorías	company, category_group, category, machine_category, location_category
Inventario	location, equipment, machine, tool, tool_stock_condition
Inspecciones y gases	inspection, gas_measurement, gas_measurement_location
Sistema	notifications, cache, cache_locks, jobs, job_batches, failed_jobs
Seeders

php artisan migrate:fresh --seed ejecuta DatabaseSeeder, que llama a:

Seeder	Qué crea
UserSeeder	Usuario de prueba (solo desarrollo)
DevUsersSeeder	5 usuarios de desarrollo para el login por letra
RoleAndAdminSeeder	Roles y el usuario administrador inicial
CategoryLocationSeeder / LocationSeeder	Categorías y ubicaciones iniciales
CategoryGroupToolsSeeder / CategoryToolsSeeder	Grupos y categorías de herramientas
CategoryMachineSeeder	Categorías de máquinas (Compresores, Motores)
ToolsSeeder / ToolStockConditionSeeder	Herramientas y estados de stock

Las credenciales de los usuarios sembrados están en los seeders. Cámbialas antes de usar el sistema en producción.

Pruebas
bash
php artisan test
Seguridad
El .env contiene credenciales y nunca debe subirse. Usa .env.example (sin valores reales) como plantilla.
Cada desarrollador usa su propio .env local.
En producción: APP_ENV=production, APP_DEBUG=false, una APP_KEY nueva (php artisan key:generate) y contraseñas distintas a las de desarrollo.
Si una contraseña o clave se sube por error a GitHub, cámbiala de inmediato: borrar el archivo después no la elimina del historial.
Licencia

MIT
