# Docker en este proyecto

Este proyecto usa Docker para levantar dos APIs PHP y una base MySQL compartida.

```text
localhost:8001 -> api-simple
localhost:8002 -> api-completa
localhost:3307 -> MySQL
```

## Servicios

| Servicio | Para que sirve |
|---|---|
| `database` | MySQL con los datos de ejemplo |
| `api-simple` | primera API, con enrutado directo en `index.php` |
| `api-completa` | API mas ordenada, con `Router`, DTOs, validators y middleware |

## Levantar todo

Desde la raiz del proyecto:

```powershell
docker compose up --build
```

La primera vez Docker descarga imagenes, instala dependencias y crea la base de datos.

## Probar en el navegador

```text
http://localhost:8001
http://localhost:8002
```

## Endpoints utiles

API simple:

```text
GET  http://localhost:8001/productos
POST http://localhost:8001/login
GET  http://localhost:8001/perfil
```

API completa:

```text
GET  http://localhost:8002/productos
POST http://localhost:8002/login
GET  http://localhost:8002/perfil
POST http://localhost:8002/logout
```

## Ver estado

```powershell
docker compose ps
```

## Ver logs

Todos los servicios:

```powershell
docker compose logs -f
```

Solo una API:

```powershell
docker compose logs -f api-simple
docker compose logs -f api-completa
```

## Detener

```powershell
docker compose down
```

## Empezar desde cero

```powershell
docker compose down -v
docker compose up --build
```

`down -v` borra el volumen de MySQL. Eso elimina los datos guardados por Docker.

## Variables importantes

El archivo `.env.docker.example` muestra las variables que se pueden configurar:

```env
SECRET_KEY=
APP_ENV=development
TOKEN_LIFETIME=3600
FRONTEND_ORIGIN=http://localhost:5173
DB_PASSWORD=utu_password
MYSQL_ROOT_PASSWORD=root_password
API_SIMPLE_PORT=8001
API_COMPLETA_PORT=8002
MYSQL_PORT=3307
```

`SECRET_KEY` se usa para firmar tokens. En un proyecto real no se sube una clave secreta a Git.

