<?php

/**
 * CLASE AUTHMIDDLEWARE (middleware de autenticacion)
 * ==================================================================
 * ?QUE ES UN MIDDLEWARE?
 *
 * Es un filtro que corre ANTES de que el pedido llegue al controller.
 * La idea es: "revisa esto primero, y si no pasa, ni te molestes en
 * seguir". El nombre es literal: queda "en el medio" (middle) entre
 * el pedido que llega y el controller que lo atiende.
 *
 * En api-simple (que no tiene Router ni middleware) cada controller pide
 * el login por su cuenta, adentro del metodo (`requireLogin()` al
 * principio de store(), de destroy(), etc. - mira
 * controllers/ProductController.php de api-simple). Eso funciona, pero
 * mezcla dos preguntas distintas: "?quien puede entrar a esta ruta?"
 * (autenticacion) y "?que hace esta ruta?" (el trabajo del
 * controller).
 *
 * Aca esa primera pregunta se contesta en UN solo lugar (este
 * archivo) y se declara al lado de cada ruta, en routes.php:
 *
 *     $router->add('DELETE', '/productos/{id}', 'ProductController', 'destroy', 'admin');
 *                                                                                  ^^^^^^^
 *                                                                    con que middleware corre
 *
 * Asi, con solo mirar routes.php, sabes que rutas piden login y
 * cuales no, sin tener que abrir cada controller.
 *
 * ------------------------------------------------------------------
 * ?QUIEN LO LLAMA?
 *
 * Router::dispatch(), justo antes de instanciar el controller. Mira
 * ahi el "AuthMiddleware::handle($route['middleware']);".
 * ==================================================================
 */
class AuthMiddleware
{
    /**
     * El usuario del token, una vez validado. Lo dejamos guardado aca
     * (en una propiedad ESTATICA, compartida por toda la clase) para
     * que el controller lo pueda pedir despues con self::user(), sin
     * tener que leer el token de nuevo.
     */
    private static ?array $user = null;

    /**
     * Corre el filtro que le corresponda a la ruta.
     *
     * @param string|null $requirement  null (ruta publica), 'auth'
     *                                  (hay que estar logueado) o
     *                                  'admin' (hay que ser admin).
     */
    public static function handle(?string $requirement): void
    {
        // Ruta publica: no hay nada que revisar.
        if ($requirement === null) {
            return;
        }

        // Token::read() busca la cookie HttpOnly, valida el JWT y devuelve
        // sus datos. Si no hay cookie o el JWT no sirve, devuelve null.
        $user = Token::read();

        // 401 = "no se quien sos". Corta aca (Response::error() hace exit).
        if ($user === null) {
            Response::error('Tenes que iniciar sesion.', 401);
        }

        // 403 = "se quien sos, pero no podes hacer esto".
        if ($requirement === 'admin' && $user['rol'] !== 'admin') {
            Response::error('Solo un administrador puede hacer esto.', 403);
        }

        self::$user = $user;
    }

    /** El usuario logueado, para el controller que lo necesite (ej. profile()). */
    public static function user(): ?array
    {
        return self::$user;
    }
}

