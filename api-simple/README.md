# API simple - recorrido explicito

Esta aplicacion sirve para empezar. Todo el enrutado esta en `index.php` con un `switch`, asi se ve el camino completo de una peticion sin tener que entender todavia una clase Router.


## Por que se llama "simple"

"Simple" no significa que sea incompleta. Esta API tiene login, roles, JWT, MySQL, PDO y CRUD.

Lo que no tiene es rate limiter. Eso queda en `api-completa`, donde se muestra como agregar una libreria externa para limitar demasiadas peticiones.

Se llama simple porque el recorrido esta escrito de forma directa:

```text
index.php tiene el switch
cada controller pide login a mano
cada controller valida a mano
no hay Router separado
no hay middleware separado
no hay DTOs
```

O sea: es una API completa para mirar como referencia, pero con menos abstracciones que `api-completa`.

La idea es aprender primero el recorrido:

```text
pedido HTTP
  -> index.php lee metodo y ruta
  -> switch elige el controller
  -> controller lee/valida datos simples
  -> service aplica reglas del negocio
  -> repository consulta MySQL con PDO
  -> Response devuelve JSON
```

## Que se aprende aca

| Tema | Donde verlo |
|---|---|
| Entrada unica de la API | `index.php` |
| Rutas sin Router | `switch (true)` en `index.php` |
| JSON de entrada | `requestData()` en `core/helpers.php` |
| Respuestas JSON | `core/Response.php` |
| Login manual | `requireLogin()` en `core/helpers.php` |
| Roles | `requireAdmin()` en `core/helpers.php` |
| Reglas del sistema | `services/` |
| SQL seguro | `repositories/` con consultas preparadas |

Las funciones sueltas de `core/helpers.php` son ayudas compartidas.
`AuthController` y `ProductController` son clases: agrupan los metodos que atienden las rutas.

En [api-completa](../api-completa/README.md), el mismo trabajo se organiza con Router, middleware, Controller padre, validators y DTOs.


## Para que sirve cada carpeta y archivo

| Archivo o carpeta | Para que sirve |
|---|---|
| `index.php` | Es la entrada de la API. Lee metodo/ruta y decide que controller ejecutar. |
| `config.php` | Lee `.env` y define constantes como `DB_HOST`, `SECRET_KEY` y `APP_ENV`. |
| `.env.example` | Plantilla para crear el `.env` local. Se puede subir a Git porque no tiene secretos reales. |
| `.env` | Configuracion real de tu maquina. No se sube a Git. |
| `database.sql` | Crea la base, tablas y datos de prueba. |
| `composer.json` | Lista de librerias externas que necesita el proyecto. |
| `composer.lock` | Versiones exactas instaladas por Composer. Ayuda a que todos usen lo mismo. |
| `vendor/` | Carpeta creada por Composer con librerias externas. No se edita a mano. |
| `core/Database.php` | Abre la conexion PDO a MySQL. |
| `core/Response.php` | Devuelve respuestas JSON y status HTTP. |
| `core/Token.php` | Crea y valida JWT usando la libreria `firebase/php-jwt`. |
| `core/helpers.php` | Funciones comunes: leer JSON, exigir login y exigir admin. |
| `controllers/` | Reciben HTTP: leen datos, validan lo basico, llaman al service y responden. |
| `services/` | Tienen las reglas del negocio. Ejemplo: no vender mas stock del disponible. |
| `repositories/` | Hablan con la base de datos usando PDO y consultas preparadas. |
| `models/` | Representan objetos del sistema, como `User` y `Product`. |

## Composer

Composer es el gestor de dependencias de PHP. Es parecido a `npm` en JavaScript.

Sirve para instalar librerias externas sin copiarlas a mano.

En esta API se usa principalmente para JWT:

```json
{
  "require": {
    "firebase/php-jwt": "^7.1"
  }
}
```

Eso significa:

```text
Este proyecto necesita la libreria firebase/php-jwt.
```

Esa libreria se usa en:

```text
core/Token.php
```

Y sirve para:

```text
crear tokens JWT
validar tokens JWT
verificar la firma
verificar vencimiento
```

Cuando ejecutas:

```bash
composer install
```

Composer lee `composer.json` y `composer.lock`, descarga las librerias y crea la carpeta `vendor/`.

Despues `index.php` carga Composer con esta linea:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

`autoload.php` permite usar clases de librerias sin hacer un `require_once` por cada archivo interno.

Resumen:

| Pieza | Significado |
|---|---|
| `composer.json` | Que librerias necesita el proyecto. |
| `composer.lock` | Que versiones exactas quedaron instaladas. |
| `vendor/` | Donde Composer guarda el codigo descargado. |
| `vendor/autoload.php` | Archivo que carga automaticamente las clases externas. |

Por que usamos libreria para JWT:

```text
Los tokens son seguridad.
En seguridad no conviene inventar algoritmos propios.
Usamos una libreria conocida y mantenida.
```

## PDO y Repository

La API usa MySQL mediante PDO.

PDO aparece en:

```text
core/Database.php
```

Ahi se crea la conexion:

```php
self::$connection = new PDO($dsn, DB_USER, DB_PASSWORD, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
```

Los repositories reciben esa conexion y hacen consultas preparadas:

```php
$query = $this->db->prepare($sql);
$query->execute([':id' => $id]);
```

La idea es:

```text
Controller no sabe SQL.
Service no sabe SQL.
Repository si sabe SQL.
```

`Repository` es una clase abstracta porque no representa una tabla concreta. Solo guarda la conexion comun para `UserRepository` y `ProductRepository`.

## Que le falta comparada con api-completa

A esta API no le faltan funcionalidades basicas: tiene login, roles y CRUD.

Lo que le falta es organizacion mas profesional:

| En api-simple | En api-completa |
|---|---|
| Rutas dentro de `index.php` con `switch` | Rutas separadas en `routes.php` |
| Cada controller llama `requireLogin()` | Un middleware revisa login antes del controller |
| Validacion escrita dentro del controller | Validators separados en `validators/` |
| Datos pasan como arrays y parametros | DTOs para transportar datos validados |
| Token viaja como Bearer en JSON/header | Token viaja en cookie `HttpOnly` |
| No tiene `/logout` real | Tiene `/logout` para borrar cookie |
| No tiene rate limiter | Tiene rate limiter con libreria Symfony |
| Menos archivos, mas directo | Mas archivos, mas ordenado para crecer |

Entonces:

```text
api-simple sirve para entender el recorrido.
api-completa sirve para ver como se ordena cuando el proyecto crece.
```

## Endpoints

| Metodo | Ruta | Que hace | Acceso |
|---|---|---|---|
| `POST` | `/registro` | Crear una cuenta | Publico |
| `POST` | `/login` | Iniciar sesion y devolver un JWT | Publico |
| `GET` | `/perfil` | Ver usuario autenticado | Bearer token |
| `GET` | `/productos` | Listar productos | Publico |
| `GET` | `/productos/{id}` | Ver un producto | Publico |
| `POST` | `/productos` | Crear producto | Bearer token |
| `PUT` | `/productos/{id}` | Modificar producto | Bearer token |
| `DELETE` | `/productos/{id}` | Borrar producto | Solo admin |
| `POST` | `/productos/{id}/vender` | Vender y descontar stock | Bearer token |

El listado acepta filtro opcional:

```http
GET /productos?categoria=audio
```

No hay `GET /` a proposito. En produccion no conviene publicar una pantalla de ayuda con informacion interna de la API.

## Token Bearer

Cuando el login sale bien, la API devuelve un token en el JSON.

En las rutas protegidas se manda asi:

```http
Authorization: Bearer TOKEN_AQUI
```

API simple no tiene `/logout`: cerrar sesion significa borrar el token del frontend. El token sigue siendo valido hasta vencer.

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

Revisa `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `SECRET_KEY` y `APP_ENV`.
Usa `APP_ENV=development` en local y `APP_ENV=production` antes de publicar.
El `.env` no se sube a Git.

3. Instalar dependencias:

```bash
composer install
```

4. Iniciar servidor:

```bash
cd api-simple
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

## Para explicar en clase

1. Abrir `index.php`.
2. Buscar `GET /productos` en el switch.
3. Entrar a `ProductController::index()`.
4. Ver como llama al service.
5. Ver como el service llama al repository.
6. Mostrar la consulta preparada en PDO.
7. Volver y comparar con `api-completa/routes.php`.