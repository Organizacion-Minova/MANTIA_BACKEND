# MANTIA - Entorno Docker

## Requisitos
- Docker Desktop
- WSL 2 (si se utiliza Windows)
- Git

## Descargar proyectos

git clone URL_DEL_BACKEND MANTIA_BACKEND
git clone URL_DEL_FRONTEND MANTIA_FRONTEND

## Configuración

1. Copiar .env.example a .env.
2. Configurar DB_PASSWORD.
3. Copiar MANTIA_BACKEND/.env.docker.example a MANTIA_BACKEND/.env.docker.
4. Configurar las variables necesarias, generar APP_KEY y definir un BACKUP_ADMIN_KEY aleatorio y privado. Se pedirá para las acciones de backup posteriores a la primera copia.

## Iniciar el proyecto

docker compose up --build -d

## Direcciones

- Frontend: http://localhost:5173
- Backend: http://localhost:8000
- PostgreSQL: localhost:5433

## Detener el proyecto

docker compose down
