<?php

/**
 * ==================================================================
 * EL MAPA DE LA API
 * ==================================================================
 * Todas las direcciones que existen, juntas y en una sola pantalla.
 * Si alguien pregunta "?que se le puede pedir a esta API?", se le
 * muestra este archivo y listo.
 *
 * Cada linea se lee asi:
 *
 *   metodo HTTP  |  direccion  |  que clase atiende  |  que metodo
 *
 * ------------------------------------------------------------------
 * FIJATE EN /productos: la MISMA direccion hace cosas distintas
 * segun el metodo (GET lista, POST crea). Eso es REST.
 *
 * Y {id} es un comodin: vale para /productos/1, /productos/2, etc.
 * Ese numero le llega al metodo como parametro.
 *
 * (Las direcciones quedan en espanol porque son la cara visible de
 * la API: es lo que escriben los que la consumen. El codigo, en
 * cambio, va en ingles, que es la convencion.)
 *
 * ------------------------------------------------------------------
 * EL QUINTO PARAMETRO: EL MIDDLEWARE
 *
 * Ahi se declara quien puede entrar a esa ruta, ANTES de que el
 * pedido llegue al controller (ver core/AuthMiddleware.php):
 *
 *   (nada)   -> ruta publica, cualquiera puede pedirla
 *   'auth'   -> hay que estar logueado (cualquier rol)
 *   'admin'  -> hay que estar logueado Y ser admin
 * ==================================================================
 */

$router = new Router();


// ---- Entrar al sistema -------------------------------------------
$router->add('POST', '/registro', 'AuthController', 'register');
$router->add('POST', '/login',    'AuthController', 'login');

// Logout es publico para poder borrar incluso una cookie vencida o invalida.
$router->add('POST', '/logout',   'AuthController', 'logout');
$router->add('GET',  '/perfil',   'AuthController', 'profile', 'auth');

// ---- Productos ---------------------------------------------------
$router->add('GET',    '/productos',      'ProductController', 'listProducts');
$router->add('GET',    '/productos/{id}', 'ProductController', 'getProduct');
$router->add('POST',   '/productos',      'ProductController', 'createProduct', 'auth');
$router->add('PATCH',  '/productos/{id}', 'ProductController', 'updateProduct', 'auth');
$router->add('DELETE', '/productos/{id}', 'ProductController', 'deleteProduct', 'admin');

// Una accion que no es un CRUD: vender descuenta stock.
$router->add('POST', '/productos/{id}/vender', 'ProductController', 'sellProduct', 'auth');

return $router;

