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
| JSON de entrada | `getJsonBody()` en `core/helpers.php` |
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
| `PATCH` | `/productos/{id}` | Modificar producto | Bearer token |
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

Esta seccion asume que no tenes nada instalado todavia. Segui los pasos en orden, sin saltear ninguno.

### Paso 0: Que necesitas tener instalado

| Herramienta | Para que sirve | Como conseguirla |
|---|---|---|
| PHP 8 o superior | Ejecuta el codigo de la API | En Windows, lo mas facil es instalar [Laragon](https://laragon.org/download/) o [XAMPP](https://www.apachefriends.org/es/index.html): traen PHP y MySQL juntos, sin configurar nada aparte |
| Extension `pdo_mysql` de PHP | Permite que PHP hable con MySQL | Ya viene activada en Laragon/XAMPP. Si instalaste PHP "a mano", hay que habilitarla en `php.ini` |
| MySQL (o MariaDB) | Guarda los datos (usuarios, productos) | Viene incluido en Laragon/XAMPP |
| [Composer](https://getcomposer.org/download/) | Descarga las librerias que usa el proyecto (ver seccion "Composer" mas abajo) | Instalador para Windows en el link. Laragon tambien lo puede instalar desde su menu |
| Postman o Insomnia (opcional pero recomendado) | Probar `POST`, `PATCH` y `DELETE`, que no se pueden probar solo desde el navegador | [Postman](https://www.postman.com/downloads/) |

Verifica que todo quedo instalado y visible desde la terminal. Abri una terminal (PowerShell) y corre, uno por uno:

```powershell
php -v
composer -V
mysql --version
```

Los tres comandos tienen que devolver una version. Si alguno da error tipo "no se reconoce como un comando", esa herramienta no quedo bien instalada o no esta en el PATH: volve a instalarla o reinicia la terminal (a veces alcanza con cerrarla y abrirla de nuevo despues de instalar algo).

### Paso 1: Ubicarte en la carpeta correcta

Todos los comandos de esta guia se ejecutan desde la carpeta `api-simple/` (la que tiene este mismo `README.md` adentro, no la carpeta raiz del repositorio que tiene `api-simple/` y `api-completa/` juntas).

```powershell
cd api-simple
```

Si te perdiste, corre `ls` (o `dir`): tendrias que ver `index.php`, `config.php`, `composer.json`, etc.

### Paso 2: Crear la base de datos

Con MySQL corriendo (en Laragon/XAMPP se prende desde su panel), importa `database.sql`. Elegi una opcion:

**Opcion A - linea de comandos:**

```powershell
mysql -u root -p < database.sql
```

Va a pedir la contrasena del usuario `root` de MySQL. Si nunca la configuraste (comun en instalaciones locales tipo Laragon), probablemente sea vacia: apreta Enter sin escribir nada.

**Opcion B - phpMyAdmin (mas visual, viene con Laragon/XAMPP):**

1. Abrir phpMyAdmin (`http://localhost/phpmyadmin` en la mayoria de las instalaciones).
2. Pestana "Importar".
3. Elegir el archivo `database.sql` de esta carpeta.
4. Click en "Continuar" / "Import".

Cualquiera de las dos opciones crea la base `utu_demo`, las tablas `usuarios` y `productos`, y carga usuarios y productos de prueba.

### Paso 3: Crear el archivo `.env`

La API necesita un archivo `.env` con su configuracion local (ver seccion "Para que sirve cada carpeta y archivo" mas arriba). Se crea copiando la plantilla:

```powershell
Copy-Item .env.example .env
```

(En Windows CMD en lugar de PowerShell seria `copy .env.example .env`; en Linux/Mac, `cp .env.example .env`.)

Ahora abri el `.env` recien creado con un editor de texto (VS Code, Notepad++, etc.) y revisa:

| Variable | Que poner |
|---|---|
| `SECRET_KEY` | Una clave al azar, nunca la de ejemplo. Generala corriendo `php -r "echo bin2hex(random_bytes(32));"` y pegando el resultado |
| `APP_ENV` | `development` mientras estas aprendiendo/probando en tu maquina |
| `DB_HOST` | `localhost` (dejalo asi salvo que tu MySQL corra en otro lado) |
| `DB_NAME` | `utu_demo` (tiene que coincidir con lo que creo `database.sql`) |
| `DB_USER` | El usuario de tu MySQL, normalmente `root` en instalaciones locales |
| `DB_PASSWORD` | La contrasena de ese usuario. Vacio si no le pusiste ninguna |

Si `SECRET_KEY` queda vacia o igual al valor de ejemplo, la API se niega a arrancar a proposito (es una medida de seguridad, no un bug: ver `config.php`).

El `.env` no se sube a Git: cada uno tiene el suyo, con sus propios datos.

### Paso 4: Instalar las dependencias

```powershell
composer install
```

Esto lee `composer.json` y descarga la libreria `firebase/php-jwt` dentro de una carpeta nueva `vendor/`. Puede tardar unos segundos. Si no corres este paso, la API va a fallar apenas intente crear un token (error tipo "Class Firebase\JWT\JWT not found").

### Paso 5: Iniciar el servidor

Con PHP trae un servidor propio para desarrollo, no hace falta instalar Apache ni Nginx para probar localmente:

```powershell
php -S localhost:8000 index.php
```

Si ves algo como `PHP 8.x Development Server ... started`, quedo levantada. Dejala corriendo ahi: esa terminal queda "ocupada" mientras la API esta prendida. Para pararla, `Ctrl + C`.

### Paso 6: Probar que funciona

Abri el navegador en:

```text
http://localhost:8000/productos
```

Si ves una lista de productos en formato JSON, todo esta funcionando. Si ves una pagina en blanco o un error, revisa la seccion de abajo.

### Errores comunes al levantarla

| Que ves | Que significa | Como arreglarlo |
|---|---|---|
| `APP_ENV debe ser development o production` | Falta el `.env`, o `APP_ENV` esta mal escrito o vacio | Revisa que exista `.env` (no `.env.example`) y que diga `APP_ENV=development` |
| `Falta configurar SECRET_KEY...` | `SECRET_KEY` esta vacia o quedo con el valor de ejemplo | Genera una clave nueva (ver Paso 3) y pegala en `.env` |
| `could not find driver` | Falta la extension `pdo_mysql` de PHP | Con Laragon/XAMPP ya deberia estar. Si instalaste PHP manual, edita `php.ini` y descomenta (saca el `;`) la linea `extension=pdo_mysql`, despues reinicia el servidor |
| `SQLSTATE[HY000] [1045] Access denied for user...` | `DB_USER`/`DB_PASSWORD` del `.env` no coinciden con tu MySQL | Revisa esos dos valores en `.env` |
| `SQLSTATE[HY000] [1049] Unknown database 'utu_demo'` | No se importo `database.sql` todavia | Volve al Paso 2 |
| `Failed to listen on localhost:8000... Address already in use` | Ya hay algo corriendo en ese puerto (quiza otra terminal con la misma API) | Cerra la otra terminal, o arranca en otro puerto: `php -S localhost:8080 index.php` |
| `Class "Firebase\JWT\JWT" not found` | No corriste `composer install`, o no existe la carpeta `vendor/` | Volve al Paso 4 |
| Pagina en blanco sin ningun mensaje | Suele ser un error de PHP que no se esta mostrando | Mira la terminal donde corre `php -S`: los errores se imprimen ahi |

### Alternativa: levantarla con Docker

Si preferis no instalar PHP, Composer ni MySQL a mano, esta carpeta (`api-simple/`) trae todo lo necesario para levantarse sola con Docker: no hace falta tener descargado el resto del repositorio ni la otra API. Es otra forma de llegar al mismo resultado del Paso 0 al Paso 6; no hace falta hacer las dos.

Necesitas [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y abierto.

1. Ubicate en esta carpeta (`api-simple/`) y crea el `.env` para Docker:

```powershell
Copy-Item .env.docker.example .env
```

2. Genera una clave y pegala en `SECRET_KEY` dentro de ese `.env`:

```powershell
php -r "echo bin2hex(random_bytes(32));"
```

3. Levantar todo (API + su propia base MySQL):

```powershell
docker compose up --build
```

La primera vez descarga imagenes, instala dependencias con Composer dentro del container y crea la base de datos con `database.sql`. Dejalo corriendo en esa terminal.

4. Probar:

```text
http://localhost:8001/productos
```

Comandos utiles, desde esta misma carpeta:

```powershell
docker compose ps            # ver containers
docker compose logs -f       # ver logs
docker compose down          # detener
docker compose down -v       # detener y borrar tambien la base de datos
```

Como esta API tiene su propio `compose.yaml`, su propio `Dockerfile` y su propia base de datos, la carpeta `api-simple/` se puede copiar sola (sin `api-completa/` ni el resto del repositorio) a otra maquina y levantarse igual.

## Como probar pedidos

### Peticiones GET (desde el navegador)

Para las rutas publicas de solo lectura alcanza con pegar la URL en el navegador:

```text
http://localhost:8000/productos
http://localhost:8000/productos/1
```

### Peticiones POST, PATCH y DELETE (con Postman o Insomnia)

El navegador, escribiendo una URL, solo puede hacer `GET`. Para el resto de los metodos hace falta una herramienta como Postman:

1. Crear una nueva peticion.
2. Elegir el metodo (`POST`, `PATCH` o `DELETE`) y pegar la URL, por ejemplo `http://localhost:8000/login`.
3. Si la peticion manda datos (como `/login` o `/registro`), ir a la pestana "Body", elegir "raw" y tipo "JSON", y escribir el JSON. Por ejemplo, para `/login`:

```json
{
  "email": "admin@utu.edu.uy",
  "clave": "admin123"
}
```

4. Enviar (`Send`). La respuesta trae un `token` si el login sale bien.
5. Para las rutas protegidas (como crear un producto), copiar ese token y agregarlo en la pestana "Headers" de la siguiente peticion:

```text
Authorization: Bearer TOKEN_AQUI
```

Usuarios de prueba:

| Email | Contrasena | Rol |
|---|---|---|
| `admin@utu.edu.uy` | `admin123` | `admin` |
| `alumno@utu.edu.uy` | `alumno123` | `usuario` |

## Para explicar en clase

1. Abrir `index.php`.
2. Buscar `GET /productos` en el switch.
3. Entrar a `ProductController::listProducts()`.
4. Ver como llama al service.
5. Ver como el service llama al repository.
6. Mostrar la consulta preparada en PDO.
7. Volver y comparar con `api-completa/routes.php`.