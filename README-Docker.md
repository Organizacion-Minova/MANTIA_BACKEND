# MANTIA con Docker

Todo corre con un solo comando: base de datos, backend, frontend, correo local (Mailpit), cola de correos y tareas programadas.

## Requisitos

- Docker Desktop (en Windows con WSL 2 activado) o Docker en Linux.
- Git.

## Estructura de carpetas (obligatoria)

Los dos repositorios van **uno al lado del otro**, con estos nombres exactos:

```
Proyectos/
├── MANTIA_BACKEND/    ← aquí está docker-compose.yml
└── MANTIA_FRONTEND/
```

```bash
mkdir Proyectos && cd Proyectos
git clone URL_DEL_BACKEND MANTIA_BACKEND
git clone URL_DEL_FRONTEND MANTIA_FRONTEND
```

**Todos los comandos `docker compose` se ejecutan dentro de `MANTIA_BACKEND`.**

---

## Primera vez (compañeros, base de datos nueva)

```bash
cd MANTIA_BACKEND
cp .env.docker.example .env.docker
```

1. Abre `.env.docker` y cambia `POSTGRES_PASSWORD` y `DB_PASSWORD` (la **misma** contraseña en las dos).
2. Genera la APP_KEY:
   ```bash
   docker compose build
   docker compose run --rm --no-deps backend php artisan key:generate --show
   ```
   Copia lo que sale (empieza por `base64:`) y pégalo en `APP_KEY=` dentro de `.env.docker`.
3. Pon un valor largo y privado en `BACKUP_ADMIN_KEY=`.
4. Levanta todo (las migraciones se aplican solas):
   ```bash
   docker compose up -d
   ```
5. Carga los datos de ejemplo (roles, ubicaciones, máquinas...):
   ```bash
   docker compose exec backend php artisan db:seed --force
   ```
6. Crea tu cuenta en http://localhost:5173 → el correo de verificación llega a **http://localhost:8025** (Mailpit).
7. Conviértete en Superadministrador:
   ```bash
   docker compose exec backend php artisan mantia:superadmin tu@correo.com
   ```

**Alternativa a los pasos 5 a 7:** si el líder te pasa su copia `Mantia_db.backup`, restáurala (ver "Restaurar una copia de la base de datos").

## Direcciones

| Qué | Dirección |
|---|---|
| Página (frontend) | http://localhost:5173 |
| API (backend) | http://localhost:8000 |
| Correos (Mailpit) | http://localhost:8025 |
| PostgreSQL (pgAdmin, DBeaver) | localhost:**5433**, usuario `postgres`, base `Mantia_db` |

## Uso diario

```bash
docker compose up -d                 # encender
docker compose down                  # apagar (los datos se conservan)
docker compose ps                    # ver qué está corriendo
docker compose logs -f backend       # ver errores del backend
```

### Después de un `git pull`

```bash
docker compose up -d --build         # reconstruye backend y frontend; las migraciones nuevas se aplican solas
```

El frontend dentro de Docker **no se actualiza solo** al editar el código: después de cambiar archivos, vuelve a correr `docker compose up -d --build frontend`.

### Si cambias `.env.docker`

```bash
docker compose up -d --force-recreate backend worker scheduler
```

## Restaurar una copia de la base de datos (`.backup`)

Con el proyecto encendido, desde `MANTIA_BACKEND`:

```bash
docker compose cp ../MANTIA_BACKUP/Mantia_db.backup db:/tmp/mantia.backup
docker compose exec db pg_restore -U postgres -d Mantia_db --clean --if-exists --no-owner /tmp/mantia.backup
docker compose exec backend php artisan migrate --force
```

El último comando agrega las tablas nuevas que no existían cuando se hizo la copia.

## Sacar una copia de la base de datos

```bash
docker compose exec -T db pg_dump -U postgres -Fc Mantia_db > ../MANTIA_BACKUP/Mantia_db.backup
```

(En PowerShell de Windows usa `cmd /c "docker compose exec -T db pg_dump -U postgres -Fc Mantia_db > ..\MANTIA_BACKUP\Mantia_db.backup"`, porque PowerShell daña los archivos binarios con `>`.)

## Empezar de cero (BORRA todos los datos)

```bash
docker compose down -v               # borra los volúmenes: base de datos y archivos subidos
docker compose up -d
docker compose exec backend php artisan db:seed --force
```

## IA sin internet (Ollama)

1. Instala Ollama **en tu PC** (no en Docker): https://ollama.com y descarga el modelo: `ollama pull qwen2.5:3b`.
2. En `.env.docker`: `AI_PROVIDER=ollama` (la `OLLAMA_URL` ya apunta a tu PC: `http://host.docker.internal:11434`).
3. **Solo en Linux:** Ollama debe escuchar en todas las interfaces. Ejecuta `sudo systemctl edit ollama`, agrega:
   ```
   [Service]
   Environment="OLLAMA_HOST=0.0.0.0"
   ```
   y luego `sudo systemctl restart ollama`.
4. `docker compose up -d --force-recreate backend worker scheduler`
5. Prueba: `docker compose exec backend php artisan mantia:sin-internet`

## Problemas comunes

| Problema | Solución |
|---|---|
| `ERROR: falta APP_KEY` | Paso 2 de "Primera vez". |
| `password authentication failed for user "postgres"` | La contraseña de `.env.docker` no es la misma con la que se creó el volumen. Pon la original, o empieza de cero con `docker compose down -v`. |
| `port is already allocated` (5433, 8000, 5173, 8025 o 1025) | Algo de tu PC ya usa ese puerto (por ejemplo `php artisan serve` o `npm run dev` fuera de Docker). Ciérralo. |
| No llega el correo | Abre http://localhost:8025 y revisa `docker compose logs worker`. |
| Aviso "volume already exists but was not created by Docker Compose" | Es normal en el PC del líder (volumen antiguo). Se puede ignorar. |
