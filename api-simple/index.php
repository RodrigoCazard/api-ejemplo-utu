<?php

/**
 * ==================================================================
 * INDEX.PHP - API SIMPLE: "SWITCH GIGANTE" (sin clase Router)
 * ==================================================================
 * Esta es una app INDEPENDIENTE, hermana de la de api-completa/. Tiene su
 * propia copia de config.php, .env, vendor/, models/, repositories/,
 * services/ y controllers/: no depende de ningun archivo de la otra
 * carpeta.
 *
 * Las dos trabajan con los mismos datos y reglas principales, pero la
 * autenticacion viaja de forma distinta y api-completa agrega /logout para
 * borrar su cookie. Aca el enrutado y los controles de acceso quedan
 * explicitos antes de pasar a las herramientas de api-completa:
 *
 *   ENRUTAR         api-simple -> switch gigante aca mismo
 *                   api-completa -> clase Router (core/Router.php) + routes.php
 *
 *   AYUDAS COMUNES  api-simple -> funciones sueltas (core/helpers.php)
 *                   api-completa -> clase Controller de la que heredan
 *
 *   LOGIN / ADMIN   api-simple -> cada metodo llama requireLogin()/
 *                              requireAdmin() al principio
 *                   api-completa -> un middleware lo resuelve ANTES de
 *                              llegar al controller (core/AuthMiddleware.php)
 *
 * ------------------------------------------------------------------
 * ?POR QUE EXISTE UNA VERSION "SIN ROUTER"?
 *
 * El Router (la clase) es mas prolijo y es como enrutan de verdad
 * Laravel, Slim, etc., pero para entenderlo hay que saber arrays,
 * explode() e indireccion de clases ($class = 'Foo'; new $class()).
 *
 * Esta version no usa nada de eso: es un switch de arriba a abajo,
 * como el codigo que se escribiria ANTES de conocer la idea de
 * "router". Es mas largo y se repite mas, pero cada caso se lee
 * de corrido sin saltar a otro archivo.
 * ==================================================================
 */

// ------------------------------------------------------------------
// 1) CARGAR LOS ARCHIVOS
// Todos viven ADENTRO de api-simple/, por eso alcanza con partir de __DIR__.
// ------------------------------------------------------------------
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

// Core: Codigo que se usa en diferentes parte de la aplicacion.
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Token.php';
require_once __DIR__ . '/core/helpers.php';

// Models
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Venta.php';
require_once __DIR__ . '/models/Review.php';

// Repositories
require_once __DIR__ . '/repositories/Repository.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/ProductRepository.php';
require_once __DIR__ . '/repositories/VentaRepository.php';
require_once __DIR__ . '/repositories/ReviewRepository.php';

// Services
require_once __DIR__ . '/services/AuthService.php';
require_once __DIR__ . '/services/ProductService.php';
require_once __DIR__ . '/services/VentaService.php';
require_once __DIR__ . '/services/ReviewService.php';

// Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProductController.php';
require_once __DIR__ . '/controllers/VentaController.php';
require_once __DIR__ . '/controllers/ReviewController.php';

// ------------------------------------------------------------------
// 2) CORS
// ------------------------------------------------------------------
// No recomendado en producción: "*" permite peticiones desde cualquier origen.
// Lo ideal es reemplazarlo por la dirección de nuestro propio frontend.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ------------------------------------------------------------------
// 3) METODO Y DIRECCION
// ------------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];

/**
 * $_SERVER['REQUEST_URI'] trae la direccion TAL CUAL la pidieron,
 * con el query string incluido si vino uno. Ejemplo:
 *
 *   /productos/3?categoria=audio
 *
 * parse_url() la separa en sus partes (path, query, host, etc.), y
 * con PHP_URL_PATH le decimos que solo nos interesa la parte de
 * ADELANTE del "?": el path.
 *
 *   parse_url('/productos/3?categoria=audio', PHP_URL_PATH)
 *   ->  '/productos/3'
 *
 * El query string ('categoria=audio') no lo tocamos aca: a eso se
 * accede aparte con $_GET (mira ProductController::index()).
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/**
 * Partimos la direccion en pedazos para poder sacar el {id} cuando
 * haga falta. Ejemplo: '/productos/3' -> ['productos', '3']
 *
 * array_values(array_filter(...)) saca los pedazos vacios que deja
 * explode cuando la direccion empieza o termina con "/".
 */
$parts = array_values(array_filter(explode('/', $path), fn($p) => $p !== ''));
$count = count($parts);

// ------------------------------------------------------------------
// 4) EL SWITCH GIGANTE
// ------------------------------------------------------------------
// switch(true) es un truco: en vez de comparar $method contra cada
// "case", cada "case" es una condicion completa (method + forma de
// la ruta) que da true o false. El switch ejecuta el PRIMER case
// que de true. Es lo mismo que un if/elseif/elseif/... pero mas
// ordenado de leer cuando hay muchos casos.
//
// El orden importa: rutas mas especificas primero.
// ------------------------------------------------------------------
/**
 * El try/catch mantiene los errores internos fuera de la respuesta.
 * El alumno sigue viendo el switch completo; solamente agregamos una red
 * de seguridad alrededor de todo el recorrido del pedido.
 */
try {
    switch (true) {

      
        // ---- Entrar al sistema -------------------------------------
        case $method === 'POST' && $count === 1 && $parts[0] === 'registro':
            // POST /registro
            // $authContoller = new AuthController();
            // $authContoller->register();
            (new AuthController())->register();
            break;

        case $method === 'POST' && $count === 1 && $parts[0] === 'login':
            // POST /login
            (new AuthController())->login();
            break;

        case $method === 'GET' && $count === 1 && $parts[0] === 'perfil':
            // GET /perfil
            (new AuthController())->profile();
            break;

        // ---- Productos ---------------------------------------------
        case $method === 'GET' && $count === 1 && $parts[0] === 'productos':
            // GET /productos
            (new ProductController())->listProducts();
            break;

        case $method === 'GET' && $count === 2 && $parts[0] === 'productos':
            // GET /productos/3  ->  $parts[1] es el id ('3')
            (new ProductController())->getProduct($parts[1]);
            break;

        case $method === 'POST' && $count === 1 && $parts[0] === 'productos':
            // POST /productos
            (new ProductController())->createProduct();
            break;

        case $method === 'PATCH' && $count === 2 && $parts[0] === 'productos':
            // PATCH /productos/3
            (new ProductController())->updateProduct($parts[1]);
            break;

        case $method === 'DELETE' && $count === 2 && $parts[0] === 'productos':
            // DELETE /productos/3
            (new ProductController())->deleteProduct($parts[1]);
            break;

        // Una accion que no es CRUD: vender descuenta stock.
        case $method === 'POST' && $count === 3 && $parts[0] === 'productos' && $parts[2] === 'vender':
            // POST /productos/3/vender  ->  $parts[1] es el id ('3')
            (new ProductController())->sellProduct($parts[1]);
            break;

        // ---- Ventas (ejercicio grupal, ver docs/ejercicio-nueva-entidad.md) ----
        // Cada grupo descomenta SOLO su case, una vez que su metodo en
        // VentaController ya existe. No descomenten el de otro grupo.
        //
        // OJO CON EL ORDEN: "GET /ventas/resumen" y "GET /ventas/3" tienen la
        // misma forma ($count === 2), asi que "resumen" tiene que ir ANTES
        // que "/ventas/{id}". Si no, "resumen" cae en el case de {id} y
        // revienta el validateId(). Es el mismo motivo por el que en general
        // "el orden importa: rutas mas especificas primero" (mira el
        // comentario al principio de este switch).

        // case $method === 'GET' && $count === 3 && $parts[0] === 'productos' && $parts[2] === 'ventas':
        //      GET /productos/3/ventas  ->  $parts[1] es el id del producto ('3')
        //     (new VentaController())->listSalesByProduct($parts[1]);
        //     break;

        // case $method === 'GET' && $count === 1 && $parts[0] === 'ventas':
        //     // GET /ventas
        //     (new VentaController())->listSales();
        //     break;

        // case $method === 'GET' && $count === 2 && $parts[0] === 'ventas' && $parts[1] === 'resumen':
        //     // GET /ventas/resumen  (tiene que ir ANTES que GET /ventas/{id})
        //     (new VentaController())->salesSummary();
        //     break;

        // case $method === 'GET' && $count === 2 && $parts[0] === 'ventas':
        //     // GET /ventas/3
        //     (new VentaController())->getSale($parts[1]);
        //     break;

        // case $method === 'POST' && $count === 1 && $parts[0] === 'ventas':
        //     // POST /ventas
        //     (new VentaController())->createSale();
        //     break;

        // case $method === 'POST' && $count === 3 && $parts[0] === 'ventas' && $parts[2] === 'anular':
        //     // POST /ventas/3/anular
        //     (new VentaController())->cancelSale($parts[1]);
        //     break;

        // ---- Reviews (entidad nueva minima, mismo espiritu que Ventas) ----
        // Esqueleto listo (tabla + Model + Repository/Service/Controller
        // vacios): descomentar cuando los metodos de ReviewController ya
        // existan.

        // case $method === 'GET' && $count === 3 && $parts[0] === 'productos' && $parts[2] === 'reviews':
        //     // GET /productos/3/reviews  ->  $parts[1] es el id del producto ('3')
        //     (new ReviewController())->listReviewsByProduct($parts[1]);
        //     break;

        // case $method === 'POST' && $count === 3 && $parts[0] === 'productos' && $parts[2] === 'reviews':
        //     // POST /productos/3/reviews  (hay que estar logueado)
        //     (new ReviewController())->createReview($parts[1]);
        //     break;

        // ---- Nada coincidio ---------------------------------------
        default:
            Response::error('No existe esa direccion, o no se puede usar con ' . $method . '.', 404);
    }
} catch (PDOException $exception) {
    // El detalle queda en el log del servidor, nunca en el JSON publico.
    error_log($exception->getMessage());

    Response::error('Ocurrio un error interno con la base de datos.', 500);
} catch (Throwable $exception) {
    error_log($exception->getMessage());

    Response::error('Ocurrio un error interno.', 500);
}


