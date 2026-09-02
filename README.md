# API REST en PHP - proyecto para aprender

Este proyecto muestra una API de productos hecha en PHP y MySQL. La idea es avanzar de a poco: primero una version simple y despues una version mas completa.

Quedando solo dos aplicaciones:

| Aplicacion | Carpeta | Idea principal | Puerto Docker |
|---|---|---|---|
| API simple | `api-simple/` | Muestra el recorrido de una request de forma directa. No tiene rate limiter. | <http://localhost:8001> |
| API completa | `api-completa/` | Ordena mejor el proyecto con `Router`, middleware, DTOs, validators, cookie HttpOnly y rate limiter con Symfony. | <http://localhost:8002> |

El material queda enfocado en dos aplicaciones: una simple para empezar y una completa para ver una estructura mas ordenada.

## Estructura

```text
api-simple/
  api-simple/       API simple: se copia y levanta sola (local o Docker)
  api-completa/     API completa: se copia y levanta sola (local o Docker)
  docs/             material de apoyo, no hace falta para levantar nada
  README.md         este archivo: explica y compara ambas APIs
```

## Que aprende cada aplicacion

### API simple

Carpeta:

```text
api-simple/
```

Sirve para ver:

- como entra una peticion por `index.php`
- como se lee el metodo HTTP
- como se lee la ruta pedida
- como un `switch` decide que controller ejecutar
- como un controller llama a un service
- como un service llama a un repository
- como se responde JSON
- como se usa un token Bearer en el header `Authorization`
- que esta version no tiene rate limiter, para mantener visible el recorrido

Ejemplos:

```text
GET  http://localhost:8001/productos
POST http://localhost:8001/login
GET  http://localhost:8001/perfil
```

En esta aplicacion, el login devuelve un token en el JSON. El cliente debe mandarlo despues asi:

```text
Authorization: Bearer TOKEN_AQUI
```

### API completa

Carpeta:

```text
api-completa/
```

Esta version muestra como ordenar una API cuando empieza a crecer.

Agrega:

- archivo `routes.php` con todas las rutas
- clase `Router`
- controller base
- DTOs para transportar datos
- validators para validar entrada
- middleware de autenticacion
- cookie `HttpOnly` para guardar el token
- rate limiter con `symfony/rate-limiter` para limitar demasiadas peticiones

Ejemplos:

```text
GET  http://localhost:8002/productos
POST http://localhost:8002/login
GET  http://localhost:8002/perfil
POST http://localhost:8002/logout
```

En esta aplicacion, el token no se copia manualmente. El backend lo guarda en una cookie `HttpOnly`.

## Cada API se levanta por separado

`api-simple/` y `api-completa/` son independientes entre si: cada una tiene adentro su propio `README.md`, su propio `composer.json`/`vendor/`, y su propia forma de levantarse con PHP local o con Docker (`compose.yaml`, `Dockerfile`, `.env.docker.example` propios).

Esto es a proposito: cualquiera de las dos carpetas se puede copiar sola (sin la otra, sin esta raiz) a otra maquina y levantarse igual, sin depender de nada mas del repositorio.

Este README raiz solo explica y compara ambas aplicaciones. Para levantar una API, segui las instrucciones de su propio README:

- [`api-simple/README.md`](api-simple/README.md#como-levantarla-localmente) - PHP local o Docker, puerto `8001` en Docker.
- [`api-completa/README.md`](api-completa/README.md#como-levantarla-localmente) - PHP local o Docker, puerto `8002` en Docker.

## Usuarios de prueba

| Email | Contrasena | Rol |
|---|---|---|
| `admin@utu.edu.uy` | `admin123` | `admin` |
| `alumno@utu.edu.uy` | `alumno123` | `usuario` |

## Orden recomendado para aprender

1. Abrir `api-simple/index.php`.
2. Seguir una ruta simple como `GET /productos`.
3. Ver `ProductController`, `ProductService` y `ProductRepository`. (En ese orden)
4. Probar login en API simple y ver el token Bearer.
5. Pasar a `api-completa/routes.php`.
6. Comparar el `switch` de API simple con el `Router` de API completa.
7. Ver DTOs, validators, middleware y cookie HttpOnly.

## Documentacion

En `docs/` hay material de apoyo:

| Archivo | Tema |
|---|---|
| `docs/http-y-rest.md` | metodos HTTP, codigos de estado y REST |
| `docs/variables-de-entorno.md` | uso de `.env` y secretos |
| `docs/git-y-gitignore.md` | que subir y que no subir a Git |
| `docs/seguridad-sqli-xss.md` | SQL injection y XSS |
| `docs/docker.md` | Docker, Compose, puertos y volumenes |
| `docs/uso-de-ia.md` | como usar IA para aprender |
| `docs/mejoras-opcionales-api-completa.md` | hoja de ruta opcional para acercar `api-completa` a una API profesional |
| `docs/ejercicio-nueva-entidad.md` | letra del ejercicio grupal: agregar el endpoint de Ventas a `api-simple` (un endpoint por grupo) |

## Nota sobre nombres

El codigo usa nombres en ingles porque es una convencion comun en programacion: `Controller`, `Service`, `Repository`, `Router`, `Request`, `Response`.






