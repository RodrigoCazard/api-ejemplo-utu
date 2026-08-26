# API completa - estructura ordenada

Esta aplicacion muestra la misma API de productos, pero organizada como se suele ordenar un proyecto cuando empieza a crecer.

A diferencia de `api-simple`, esta version si tiene rate limiter. Lo implementa con `symfony/rate-limiter` y `symfony/cache`.

El recorrido principal es:

```text
pedido HTTP
  -> index.php
  -> RateLimiter
  -> Router
  -> Middleware, si la ruta lo pide
  -> Controller
  -> Validator
  -> DTO
  -> Service
  -> Repository
  -> MySQL
  -> Response JSON
```

No usa Laravel ni otro framework. La idea es ver las piezas con PHP simple antes de usar herramientas mas grandes.

## Que agrega esta version

| Pieza | Para que sirve |
|---|---|
| `routes.php` | Tener todas las rutas juntas |
| `core/Router.php` | Buscar que controller atiende cada ruta |
| `core/AuthMiddleware.php` | Revisar login o rol antes del controller |
| `controllers/Controller.php` | Reutilizar lectura de JSON y usuario actual |
| `validators/` | Validar datos de entrada en archivos separados |
| `dtos/` | Transportar datos ya validados al service |
| `core/RateLimiter.php` | Usar Symfony RateLimiter para frenar demasiadas peticiones por IP |
| Cookie `HttpOnly` | Guardar el JWT sin que JavaScript lo pueda leer |

## Archivos importantes

```text
api-completa/
  index.php                 entrada de la API
  routes.php                mapa de rutas
  config.php                lee configuracion y .env
  database.sql              crea tablas y datos de prueba
  core/
    Router.php              enrutador
    AuthMiddleware.php      login y permisos
    RateLimiter.php         wrapper simple sobre Symfony RateLimiter
    Response.php            respuestas JSON
    Token.php               JWT y cookie
    Database.php            conexion PDO
  controllers/
    Controller.php          clase base
    AuthController.php      registro, login, logout, perfil
    ProductController.php   CRUD de productos
  validators/               validacion de entrada
  dtos/                     datos ya validados
  services/                 reglas del negocio
  repositories/             consultas SQL
  models/                   User y Product
  storage/                  archivos generados localmente
```

La carpeta `storage/` la usa Symfony Cache para guardar el estado del limite. No se sube a Git.

## Endpoints

| Metodo | Ruta | Que hace | Acceso |
|---|---|---|---|
| `POST` | `/registro` | Crear una cuenta | Publico |
| `POST` | `/login` | Iniciar sesion y crear cookie | Publico |
| `POST` | `/logout` | Cerrar sesion y borrar cookie | Publico |
| `GET` | `/perfil` | Ver usuario autenticado | Cookie de sesion |
| `GET` | `/productos` | Listar productos | Publico |
| `GET` | `/productos/{id}` | Ver un producto | Publico |
| `POST` | `/productos` | Crear producto | Login requerido |
| `PUT` | `/productos/{id}` | Modificar producto | Login requerido |
| `DELETE` | `/productos/{id}` | Borrar producto | Solo admin |
| `POST` | `/productos/{id}/vender` | Vender y descontar stock | Login requerido |

No hay `GET /` a proposito. En produccion no conviene publicar una pantalla de ayuda con informacion interna de la API.

## Como levantarla localmente

Necesitas PHP 8, Composer, la extension `pdo_mysql` y MySQL.

1. Crear la base:

```bash
mysql -u root -p < database.sql
```

Tambien se puede importar `database.sql` desde phpMyAdmin.

2. Crear `.env`:

```bash
cp .env.example .env
```

En Windows CMD:

```bat
copy .env.example .env
```

Revisa `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `SECRET_KEY`, `APP_ENV` y `FRONTEND_ORIGIN`.
Usa `APP_ENV=development` en local y `APP_ENV=production` antes de publicar.
El `.env` no se sube a Git.

3. Instalar dependencias:

```bash
composer install
```

4. Iniciar servidor:

```bash
cd api-completa
php -S localhost:8000 index.php
```

5. Probar:

```text
http://localhost:8000/productos
```

Tambien podes levantar las dos aplicaciones y MySQL con Docker siguiendo el [README principal](../README.md#levantar-con-docker).

## Como probar pedidos

Para empezar, abri en el navegador:

```text
http://localhost:8000/productos
```

Para `POST`, `PUT` y `DELETE`, usa Postman, Insomnia o el frontend que conectes a esta API.

Usuarios de prueba:

| Email | Contrasena | Rol |
|---|---|---|
| `admin@utu.edu.uy` | `admin123` | `admin` |
| `alumno@utu.edu.uy` | `alumno123` | `usuario` |

## Login con cookie HttpOnly

En esta version el login no devuelve el token para copiarlo. El backend manda una cookie `HttpOnly`.

Eso significa:

- el navegador guarda la cookie automaticamente;
- JavaScript no puede leer el token;
- en `fetch` hay que usar `credentials: 'include'`;
- el backend debe permitir el origen configurado en `FRONTEND_ORIGIN`.

Ejemplo de frontend:

```javascript
fetch('http://localhost:8000/login', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, clave })
});
```

## Rate limiter

La API completa usa una libreria para limitar peticiones:

```bash
composer require symfony/rate-limiter:7.4.* symfony/cache:7.4.*
```

La clase nuestra, `core/RateLimiter.php`, no implementa el algoritmo a mano. Solo configura Symfony y lo conecta con nuestra respuesta JSON.

En `index.php` aparece esta linea despues de responder `OPTIONS` y antes del router:

```php
RateLimiter::check(60, 60);
```

Significa:

- maximo 60 peticiones;
- cada 60 segundos;
- por IP.

Adentro se usa:

```php
new RateLimiterFactory([
    'policy' => 'sliding_window',
    'limit' => 60,
    'interval' => '60 seconds',
], $storage);
```

`sliding_window` es la estrategia de Symfony para contar peticiones recientes sin escribir nosotros el algoritmo.

Symfony necesita guardar estado entre una request y la siguiente. Para eso usamos `symfony/cache`:

```php
new CacheStorage(new FilesystemAdapter(...))
```

Entonces sigue existiendo una carpeta `storage/`, pero ya no leemos ni escribimos JSON a mano con `file_get_contents()` o `file_put_contents()`. Esa parte la maneja la libreria.

Si se supera el limite, responde:

```text
429 Too Many Requests
```

Tambien manda estos headers:

```text
Retry-After
X-RateLimit-Limit
X-RateLimit-Remaining
```

En produccion grande se suele cambiar el storage por Redis, Nginx, Cloudflare o una base de datos compartida.

## Codigos HTTP usados

| Codigo | Significa | Cuando aparece |
|---|---|---|
| `200` | OK | Salio todo bien |
| `201` | Created | Se creo algo |
| `400` | Bad Request | Datos invalidos |
| `401` | Unauthorized | Falta login o token valido |
| `403` | Forbidden | Hay login, pero falta permiso |
| `404` | Not Found | Ruta o recurso inexistente |
| `429` | Too Many Requests | Demasiadas peticiones |
| `500` | Internal Server Error | Error inesperado del servidor |

## Seguridad incluida

| Medida | Donde verla |
|---|---|
| Passwords con `password_hash()` | `services/AuthService.php` |
| Verificacion con `password_verify()` | `models/User.php` |
| JWT con libreria probada | `core/Token.php` |
| Cookie `HttpOnly` | `core/Token.php` |
| Login y permisos centralizados | `core/AuthMiddleware.php` |
| Validacion de entrada | `validators/` |
| SQL preparado contra injection | `repositories/` |
| Rate limiter con libreria Symfony | `core/RateLimiter.php` |
| Archivos internos bloqueados por Apache | `.htaccess` |

Lo que faltaria para una API real: HTTPS obligatorio, proteccion CSRF completa, logs mas serios, tests automaticos, migraciones de base de datos y un storage compartido para el rate limiter si hay varios servidores, por ejemplo Redis.

## Orden recomendado para estudiar

1. Mirar `routes.php`.
2. Seguir `GET /productos` hasta `ProductController::index()`.
3. Ver como el controller llama al service.
4. Ver como el service llama al repository.
5. Mirar una ruta protegida, por ejemplo `POST /productos`.
6. Ver como `AuthMiddleware` corta antes del controller si no hay sesion.
7. Comparar este recorrido con `api-simple/index.php`.

## Ejercicios

1. Agregar el campo `marca` al producto.
2. Agregar paginacion en `GET /productos`.
3. Crear `GET /productos/{id}/stock`.
4. Hacer borrado logico con un campo `activo`.
5. Crear una entidad `Categoria` con controller, service, repository y rutas.
6. Agregar tests automaticos para validators y services.