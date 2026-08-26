<?php

/**
 * HELPERS (funciones sueltas, sin clase)
 * ==================================================================
 * AuthController y ProductController necesitan las mismas dos cosas:
 * leer el JSON del pedido, y exigir login o rol de admin. En vez de
 * una clase para agruparlas, aca son simplemente funciones de PHP:
 * cada controller les hace un require y las llama directo
 * (requireLogin(), no $this->requireLogin()).
 *
 * Cada metodo de cada controller decide por su cuenta si necesita
 * requireLogin() o requireAdmin(), llamandola al principio (mira
 * ProductController::store(), por ejemplo). En api-completa esa decision
 * no esta en el controller: se declara en routes.php y la aplica un
 * middleware ANTES de que el controller se entere del pedido - mira
 * core/AuthMiddleware.php de api-completa para comparar los dos enfoques.
 * ==================================================================
 */

/**
 * Lee el JSON que mando el cliente y lo convierte en arreglo.
 *
 * Los datos de un POST o un PUT en formato JSON no llegan en $_POST:
 * hay que leerlos del "cuerpo" del pedido con php://input.
 */
function requestData(): array
{
    $json = file_get_contents('php://input');

    // Algunos pedidos, como un POST sin datos, pueden traer el cuerpo vacio.
    if ($json === false || trim($json) === '') {
        return [];
    }

    $data = json_decode($json, true);

    // Un JSON mal escrito es distinto de no mandar datos. Lo avisamos con
    // un error 400 para que el cliente pueda corregir su peticion.
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        Response::error('El cuerpo tiene que ser un objeto JSON valido.', 400);
    }

    return $data;
}

/**
 * EXIGE que haya un token valido.
 *
 * Si lo hay, devuelve los datos del usuario (id, nombre, rol).
 * Si no, contesta 401 y el programa TERMINA ahi mismo (acordate que
 * Response::error() hace exit).
 *
 * 401 = "no se quien sos"
 */
function requireLogin()
{
    $user = Token::read();

    if ($user === null) {
        Response::error('Tenes que iniciar sesion. Manda el token en la cabecera Authorization.', 401);
    }

    return $user;
}

/**
 * EXIGE que ademas sea administrador.
 *
 * Primero se fija que este logueado (reutiliza la funcion de arriba)
 * y despues mira el rol.
 *
 * 403 = "se quien sos, pero no podes hacer esto"
 *
 * !OJO CON LA DIFERENCIA! Es la confusion mas comun:
 *   401 = AUTENTICACION -> ?quien sos?
 *   403 = AUTORIZACION  -> ?tenes permiso?
 */
function requireAdmin()
{
    $user = requireLogin();

    if ($user['rol'] !== 'admin') {
        Response::error('Solo un administrador puede hacer esto.', 403);
    }

    return $user;
}

