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
| `PATCH` | `/productos/{id}` | Modificar producto | Login requerido |
| `DELETE` | `/productos/{id}` | Borrar producto | Solo admin |
| `POST` | `/productos/{id}/vender` | Vender y descontar stock | Login requerido |

No hay `GET /` a proposito. En produccion no conviene publicar una pantalla de ayuda con informacion interna de la API.

## Como levantarla localmente

Hay dos formas. La recomendada es Docker: un solo comando y listo, sin
instalar PHP, Composer ni MySQL. La otra es instalar todo a mano, util
si no tenes Docker o si queres entender cada pieza por separado. Esta
carpeta (`api-completa/`) es independiente de `api-simple/`: se puede
copiar sola y levantar sin la otra.

### Opcion A (recomendada): Docker, todo automatico

Necesitas [Docker Desktop](https://www.docker.com/products/docker-desktop/)
instalado y abierto. Nada mas.

```powershell
cd api-completa
docker compose up --build
```

Eso es todo. No hace falta crear ningun `.env` ni generar ninguna
clave: `compose.yaml` ya trae una `SECRET_KEY` de demo y valores por
defecto para lo demas, y la base de datos se crea sola la primera vez
con `database.sql`. La primera corrida descarga imagenes e instala
Composer adentro del container, asi que tarda un poco mas; las
siguientes son casi instantaneas.

Probar:

```text
http://localhost:8002/productos
```

Comandos utiles, desde esta misma carpeta:

```powershell
docker compose ps            # ver containers
docker compose logs -f       # ver logs
docker compose down          # detener
docker compose down -v       # detener y borrar tambien la base de datos
```

`down -v` borra el volumen de MySQL: usalo cuando quieras arrancar la
base desde cero.

**Si `database.sql` cambio** (por ejemplo, trajiste cambios nuevos con
`git pull` y aparecieron tablas que todavia no tenes): Docker solo
corre `database.sql` la PRIMERA vez que crea el volumen de MySQL: si ya
lo habias levantado antes, un `docker compose up` de nuevo no lo vuelve
a ejecutar. Para que los cambios entren, hay que borrar el volumen y
recrearlo:

```powershell
docker compose down -v
docker compose up --build
```

Eso borra los datos guardados por esa base (incluido cualquier dato
que hayas creado a mano) y los vuelve a cargar desde el `database.sql`
actual.

La clave de demo que trae `compose.yaml` sirve solo para aprender: no
la uses en un servidor real. Si en algun momento queres la tuya, copia
`.env.docker.example` a `.env` en esta misma carpeta y poné ahi tu
propia `SECRET_KEY` (generala con
`php -r "echo bin2hex(random_bytes(32));"`); `docker compose up` la
toma automaticamente sin que tengas que tocar nada mas.

Como esta API tiene su propio `compose.yaml`, su propio `Dockerfile` y
su propia base de datos, la carpeta `api-completa/` se puede copiar
sola (sin `api-simple/` ni el resto del repositorio) a otra maquina y
levantarse igual.

### Opcion B: instalar todo a mano (sin Docker)

Esta seccion asume que no tenes nada instalado todavia. Segui los pasos en orden, sin saltear ninguno.

#### Paso 0: Que necesitas tener instalado

| Herramienta | Para que sirve | Como conseguirla |
|---|---|---|
| PHP 8 o superior | Ejecuta el codigo de la API | En Windows, lo mas facil es instalar [Laragon](https://laragon.org/download/) o [XAMPP](https://www.apachefriends.org/es/index.html): traen PHP y MySQL juntos, sin configurar nada aparte |
| Extension `pdo_mysql` de PHP | Permite que PHP hable con MySQL | Ya viene activada en Laragon/XAMPP. Si instalaste PHP "a mano", hay que habilitarla en `php.ini` |
| MySQL (o MariaDB) | Guarda los datos (usuarios, productos) | Viene incluido en Laragon/XAMPP |
| [Composer](https://getcomposer.org/download/) | Descarga las librerias que usa el proyecto (JWT, Symfony RateLimiter) | Instalador para Windows en el link. Laragon tambien lo puede instalar desde su menu |
| Postman o Insomnia (opcional pero recomendado) | Probar `POST`, `PATCH` y `DELETE`, que no se pueden probar solo desde el navegador | [Postman](https://www.postman.com/downloads/) |

Verifica que todo quedo instalado, desde una terminal (PowerShell):

```powershell
php -v
composer -V
mysql --version
```

Si alguno da error tipo "no se reconoce como un comando", esa herramienta no quedo bien instalada o no esta en el PATH.

#### Paso 1: Ubicarte en la carpeta correcta

```powershell
cd api-completa
```

Tendrias que ver `index.php`, `routes.php`, `config.php`, `composer.json`, etc.

#### Paso 2: Crear la base de datos

Con MySQL corriendo, importa `database.sql`. Elegi una alternativa:

**Por linea de comandos:**

```powershell
mysql -u root -p < database.sql
```

Si nunca configuraste una contrasena para `root` (comun en instalaciones locales tipo Laragon), apreta Enter sin escribir nada.

**Por phpMyAdmin:**

1. Abrir phpMyAdmin (`http://localhost/phpmyadmin` en la mayoria de las instalaciones).
2. Pestana "Importar".
3. Elegir el archivo `database.sql` de esta carpeta.
4. Click en "Continuar" / "Import".

**Si ya tenias la base creada de antes y `database.sql` cambio** (por ejemplo, trajiste cambios nuevos con `git pull` y aparecieron tablas o columnas que todavia no tenes), hay que volver a importarla para que los cambios entren. MySQL no actualiza una base ya creada solo con volver a correr el archivo: hay que borrarla primero.

```powershell
mysql -u root -p -e "DROP DATABASE utu_demo;"
mysql -u root -p < database.sql
```

`DROP DATABASE` borra la base entera, incluido cualquier dato que hayas creado o modificado a mano. Los datos de ejemplo se cargan solos al reimportar.

#### Paso 3: Crear el archivo `.env`

```powershell
Copy-Item .env.example .env
```

Abrila con un editor de texto y revisa:

| Variable | Que poner |
|---|---|
| `SECRET_KEY` | Una clave al azar, nunca la de ejemplo. Generala con `php -r "echo bin2hex(random_bytes(32));"` |
| `APP_ENV` | `development` mientras estas aprendiendo/probando en tu maquina |
| `DB_HOST` | `localhost` |
| `DB_NAME` | `utu_demo` |
| `DB_USER` | El usuario de tu MySQL, normalmente `root` |
| `DB_PASSWORD` | La contrasena de ese usuario. Vacio si no tiene |
| `FRONTEND_ORIGIN` | La direccion del frontend que va a consumir esta API (por defecto `http://localhost:5173`). Se usa para permitir la cookie del login por CORS |

Si `SECRET_KEY` queda vacia o igual al valor de ejemplo, la API se niega a arrancar a proposito.

El `.env` no se sube a Git.

#### Paso 4: Instalar las dependencias

```powershell
composer install
```

Descarga `firebase/php-jwt`, `symfony/rate-limiter` y `symfony/cache` dentro de una carpeta nueva `vendor/`.

#### Paso 5: Iniciar el servidor

```powershell
php -S localhost:8000 index.php
```

Dejala corriendo en esa terminal. Para pararla, `Ctrl + C`.

#### Paso 6: Probar que funciona

```text
http://localhost:8000/productos
```

Si ves una lista de productos en JSON, esta funcionando.

#### Errores comunes al levantarla

| Que ves | Que significa | Como arreglarlo |
|---|---|---|
| `APP_ENV debe ser development o production` | Falta el `.env`, o `APP_ENV` esta mal escrito o vacio | Revisa que exista `.env` con `APP_ENV=development` |
| `Falta configurar SECRET_KEY...` | `SECRET_KEY` esta vacia o quedo con el valor de ejemplo | Genera una clave nueva (ver Paso 3) |
| `could not find driver` | Falta la extension `pdo_mysql` de PHP | Habilitala en `php.ini` (sacar el `;` de `extension=pdo_mysql`) y reiniciar |
| `SQLSTATE[HY000] [1045] Access denied for user...` | `DB_USER`/`DB_PASSWORD` no coinciden con tu MySQL | Revisa esos dos valores en `.env` |
| `SQLSTATE[HY000] [1049] Unknown database 'utu_demo'` | No se importo `database.sql` todavia | Volve al Paso 2 |
| `Address already in use` | Ya hay algo corriendo en ese puerto | Cerra lo otro, o `php -S localhost:8080 index.php` |
| `Class "Firebase\JWT\JWT" not found` o similar de Symfony | No corriste `composer install` | Volve al Paso 4 |

## Como probar pedidos

### Peticiones GET (desde el navegador)

```text
http://localhost:8000/productos
http://localhost:8000/productos/1
```

### Peticiones POST, PATCH y DELETE (con Postman o Insomnia)

1. Crear una nueva peticion, elegir el metodo y pegar la URL, por ejemplo `http://localhost:8000/login`.
2. Si manda datos, ir a "Body" -> "raw" -> "JSON":

```json
{
  "email": "admin@utu.edu.uy",
  "clave": "admin123"
}
```

3. Enviar. Esta API responde el login con una cookie `HttpOnly` (no con un token en el JSON, ver seccion siguiente), asi que Postman/Insomnia se encargan de guardar y reenviar esa cookie solos en las siguientes peticiones a la misma coleccion.

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
2. Seguir `GET /productos` hasta `ProductController::listProducts()`.
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

Para seguir practicando despues de estos ejercicios, [`docs/mejoras-opcionales-api-completa.md`](../docs/mejoras-opcionales-api-completa.md) tiene una hoja de ruta mas larga (validacion estricta de JSON, proteccion CSRF, venta de stock atomica, restricciones en la base, autenticacion mas resistente, tests automatizados y mas) para acercar esta API a una version profesional.