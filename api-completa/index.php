<?php

/**
 * ==================================================================
 * INDEX.PHP - API COMPLETA: CON ROUTER  -  LA PUERTA DE ENTRADA
 * ==================================================================
 * TODOS los pedidos entran por aca. No hay un login.php, un
 * productos.php y un borrar.php sueltos: hay UN solo archivo que
 * recibe todo y se lo pasa al router.
 *
 * Es hermana de api-simple/: las dos apps son independientes entre si y
 * responden exactamente igual, pero aca hay mas capas resolviendo
 * cosas por vos en un solo lugar en vez de repetirlas en cada
 * controller - el Router (en vez de un switch), la clase Controller
 * (en vez de funciones sueltas) y el AuthMiddleware (en vez de pedir
 * el login a mano en cada metodo). Mira api-simple/index.php para
 * comparar la version sin nada de eso.
 *
 * Lo que hace, en orden:
 *
 *   1. Carga los archivos de cada capa
 *   2. Mira QUE METODO usaron (GET, POST, PUT, DELETE)
 *   3. Mira QUE DIRECCION pidieron (/productos/3)
 *   4. Se lo entrega al router, que sabe quien lo atiende
 *
 * ------------------------------------------------------------------
 * EL RECORRIDO Y LAS RESPONSABILIDADES:
 *
 *   ROUTER      ?quien atiende este pedido?
 *      |
 *   MIDDLEWARE  ?esta logueado? ?tiene el rol necesario?
 *      |
 *   CONTROLLER  recibe y responde HTTP
 *      |
 *   VALIDATOR   ?los datos vienen bien?
 *      |
 *   DTO         transporta los datos validos
 *      |
 *   SERVICE     las reglas del negocio                    (el sistema)
 *      |
 *   REPOSITORY  buscar y guardar                          (los datos)
 *
 * Cada pieza conoce solo lo necesario. El controller usa validator, DTO
 * y service; el repository no sabe que existe HTTP y el controller no
 * sabe que es una tabla SQL.
 *
 * ------------------------------------------------------------------
 * NOTA SOBRE EL IDIOMA
 * El codigo (clases, metodos, variables) va en INGLES, que es la
 * convencion en programacion y lo que te vas a encontrar en Laravel,
 * en Symfony y en cualquier proyecto. Las explicaciones y los mensajes
 * quedan en espanol.
 * ==================================================================
 */

// ------------------------------------------------------------------
// 1) CARGAR LOS ARCHIVOS
// Estan agrupados por capa. El orden importa: primero las clases
// padre, despues las hijas.
// ------------------------------------------------------------------
require_once __DIR__ . '/config.php';

/**
 * LIBRERIAS EXTERNAS (las que instalamos con Composer)
 *
 * Esta unica linea carga TODAS las librerias que instalamos. Hoy es una
 * sola, firebase/php-jwt, la que se usa en PHP para los tokens.
 *
 * ?Como llego ahi? Con un comando:
 *
 *     composer require firebase/php-jwt
 *     composer require symfony/rate-limiter:7.4.* symfony/cache:7.4.*
 *
 * Composer la descargo, la dejo en la carpeta vendor/ y anoto en
 * composer.json que este proyecto la necesita. La carpeta vendor/ NO se
 * toca ni se modifica: es codigo de otra gente.
 *
 * El "autoload" es un cargador automatico: cuando PHP se encuentra con
 * una clase que no conoce, sale a buscar el archivo solo. Por eso aca
 * no hay que poner un require por cada archivo de la libreria.
 */
require_once __DIR__ . '/vendor/autoload.php';

// Core: las herramientas que usa todo el resto.
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/RateLimiter.php';
require_once __DIR__ . '/core/Token.php';
require_once __DIR__ . '/core/AuthMiddleware.php';
require_once __DIR__ . '/core/Router.php';

// Models: las cosas del problema (un usuario, un producto).
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Product.php';

// Validators: revisan la forma de los datos de cada endpoint.
require_once __DIR__ . '/validators/AuthValidator.php';
require_once __DIR__ . '/validators/ProductValidator.php';

// DTOs: transportan datos ya validados y normalizados.
require_once __DIR__ . '/dtos/RegisterDTO.php';
require_once __DIR__ . '/dtos/LoginDTO.php';
require_once __DIR__ . '/dtos/CreateProductDTO.php';
require_once __DIR__ . '/dtos/UpdateProductDTO.php';
require_once __DIR__ . '/dtos/SellProductDTO.php';

// Repositories: acceso a los datos.
require_once __DIR__ . '/repositories/Repository.php';          // clase padre
require_once __DIR__ . '/repositories/UserRepository.php';      // hija
require_once __DIR__ . '/repositories/ProductRepository.php';   // hija

// Services: las reglas del negocio.
require_once __DIR__ . '/services/AuthService.php';
require_once __DIR__ . '/services/ProductService.php';

// Controllers: la puerta con el mundo de afuera.
require_once __DIR__ . '/controllers/Controller.php';         // clase padre
require_once __DIR__ . '/controllers/AuthController.php';     // hija
require_once __DIR__ . '/controllers/ProductController.php';  // hija

// ------------------------------------------------------------------
// 2) PERMISOS PARA EL NAVEGADOR (CORS)
// Sin esto, una pagina hecha en otro puerto (por ejemplo un front en
// React) no puede consumir nuestra API: el navegador la bloquea.
// ------------------------------------------------------------------
// $_SERVER es un arreglo superglobal con informacion de la peticion.
// HTTP_ORIGIN indica desde que origen (dominio y puerto) llamo el frontend.
// El operador ?? usa null y evita un aviso si no viene desde un navegador.
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;

// Las cookies con CORS no permiten usar Access-Control-Allow-Origin: *.
// === exige que el origen recibido coincida exactamente con el configurado.
if ($origin === FRONTEND_ORIGIN) {
    // header() agrega una cabecera HTTP a la respuesta.
    header('Access-Control-Allow-Origin: ' . FRONTEND_ORIGIN);

    // Esta cabecera autoriza al navegador a enviar y recibir cookies.
    header('Access-Control-Allow-Credentials: true');

    // Vary avisa a caches que la respuesta puede cambiar segun el Origin.
    header('Vary: Origin');
}

// Cabeceras y metodos que el frontend tiene permitido utilizar.
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

// Antes de un POST o un DELETE, el navegador manda un pedido OPTIONS
// preguntando "?me dejas?". Le contestamos que si y listo.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // 204 significa que la peticion fue aceptada pero no hay cuerpo JSON.
    http_response_code(204);

    // exit termina esta peticion antes de llegar al router.
    exit;
}

// ------------------------------------------------------------------
// 3) RATE LIMITER
// ------------------------------------------------------------------
// Protege la API de demasiadas peticiones seguidas desde la misma IP.

RateLimiter::check(60, 60);

// ------------------------------------------------------------------
// 4) ?QUE METODO Y QUE DIRECCION PIDIERON?
// ------------------------------------------------------------------

// GET, POST, PUT o DELETE
$method = $_SERVER['REQUEST_METHOD'];

// La direccion, sin lo que viene despues del "?"
// Ejemplo: /productos/3?x=1  ->  /productos/3
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ------------------------------------------------------------------
// 5) AL ROUTER
// routes.php arma el router con todas las rutas y nos lo devuelve.
// ------------------------------------------------------------------
/**
 * try contiene codigo que podria lanzar una EXCEPCION.
 * catch captura esa excepcion para que la API responda JSON en lugar de
 * mostrar un error interno de PHP.
 */
try {
    $router = require __DIR__ . '/routes.php';

    $router->dispatch($method, $path);
} catch (PDOException $exception) {
    // PDOException es el tipo especifico que PDO lanza ante un fallo de BD.
    // Guardamos el detalle para poder investigar el problema, pero no
    // mostramos consultas ni informacion de la conexion al cliente.
    error_log($exception->getMessage());

    Response::error(
        'Ocurrio un error interno con la base de datos.',
        500
    );
} catch (Throwable $exception) {
    // Throwable es el tipo general: captura otras excepciones y errores PHP.
    // Tambien evitamos que cualquier otro error inesperado muestre
    // informacion interna de la aplicacion.
    error_log($exception->getMessage());

    Response::error(
        'Ocurrio un error interno.',
        500
    );
}


