# Docker en este proyecto

Cada API (`api-simple/` y `api-completa/`) trae su propio Docker: su propio `compose.yaml`, su propio `Dockerfile` y su propio `.env.docker.example`, dentro de su propia carpeta. Son independientes entre si: se puede copiar una sola carpeta a otra maquina y levantarla sin la otra y sin el resto del repositorio.

Este documento explica los conceptos generales. Para los comandos y puertos concretos de cada API, mira su propio README:

- [`api-simple/README.md`](../api-simple/README.md#alternativa-levantarla-con-docker)
- [`api-completa/README.md`](../api-completa/README.md#alternativa-levantarla-con-docker)

## Que arma cada `compose.yaml`

Cada API levanta dos servicios propios:

| Servicio | Para que sirve |
|---|---|
| `database` | Un MySQL propio de esa API, con los datos de ejemplo de su `database.sql` |
| `api` | La API en si, corriendo con PHP + Apache |

Como cada API tiene su propia base (`database`) y su propio volumen, no comparten datos entre si: son dos proyectos Docker completamente separados, aunque ambos usen el mismo esquema de tablas.

## Puertos por defecto

| API | Puerto de la API | Puerto de MySQL |
|---|---|---|
| `api-simple` | `8001` | `3307` |
| `api-completa` | `8002` | `3308` |

Son distintos a proposito: si alguna vez levantas las dos al mismo tiempo (cada una desde su propia carpeta, con su propio `docker compose up`), no chocan entre si. Se pueden cambiar editando `API_PORT` y `MYSQL_PORT` en el `.env` de cada carpeta.

## Comandos utiles

Se ejecutan parado en la carpeta de la API que queres controlar (`api-simple/` o `api-completa/`):

```powershell
docker compose up --build   # levantar (primera vez o tras cambiar el Dockerfile)
docker compose ps           # ver containers
docker compose logs -f      # ver logs
docker compose down         # detener
docker compose down -v      # detener y borrar tambien el volumen de MySQL
```

`down -v` borra los datos guardados por esa base. Usarlo solo cuando quieras empezar desde cero.

## Variables importantes

El `.env.docker.example` de cada carpeta muestra las variables que se pueden configurar. Por ejemplo, en `api-simple/.env.docker.example`:

```env
SECRET_KEY=
APP_ENV=development
TOKEN_LIFETIME=3600
DB_PASSWORD=utu_password
MYSQL_ROOT_PASSWORD=root_password
API_PORT=8001
MYSQL_PORT=3307
```

`api-completa/.env.docker.example` tiene ademas `FRONTEND_ORIGIN`, porque esa API usa una cookie `HttpOnly` y necesita saber que origen del frontend puede recibirla por CORS.

`SECRET_KEY` se usa para firmar tokens. En un proyecto real no se sube una clave secreta a Git; por eso cada `.env` (no `.env.docker.example`) esta en `.gitignore`.
