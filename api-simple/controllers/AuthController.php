<?php

/**
 * CONTROLLER DE AUTENTICACION
 * ==================================================================
 * Registro, login y perfil.
 *
 * EL CIRCUITO COMPLETO:
 *
 *   1. El usuario manda email y contrasena          -> POST /login
 *   2. El SERVICE busca el usuario y verifica la contrasena
 *   3. Si esta todo bien, devuelve un TOKEN
 *   4. El cliente guarda ese token
 *   5. En cada pedido lo manda: Authorization: Bearer <token>
 *
 * requestData() y requireLogin() estan en core/helpers.php.
 * ==================================================================
 */
class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    /**
     * POST /registro
     * Recibe: { "nombre": "...", "email": "...", "clave": "..." }
     */
    public function register()
    {
        $data = requestData();

        // Primero conservamos los valores tal como llegaron. Asi podemos
        // comprobar su tipo antes de usar funciones como trim() o strlen().
        $name     = $data['nombre'] ?? null;
        $email    = $data['email'] ?? null;
        $password = $data['clave'] ?? null;

        // ---- VALIDACION ------------------------------------------
        // NUNCA hay que confiar en lo que manda el cliente.
        $errors = [];

        if (!is_string($name) || strlen(trim($name)) < 3) {
            $errors[] = 'El nombre tiene que tener al menos 3 letras.';
        }

        // filter_var es la forma correcta de validar un email en PHP.
        if (!is_string($email) || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email no es valido.';
        }

        if (!is_string($password) || strlen($password) < 6) {
            $errors[] = 'La contrasena tiene que tener al menos 6 caracteres.';
        }

        if (count($errors) > 0) {
            Response::error('Revisa los datos.', 400, $errors);
        }

        // Si el email ya existe o no, lo decide el SERVICE:
        // para saberlo hay que ir a buscar a la base.
        $user = $this->service->register(trim($name), trim($email), $password);

        Response::success($user, 'Cuenta creada.', 201);
    }

    /**
     * POST /login
     * Recibe: { "email": "...", "clave": "..." }
     */
    public function login()
    {
        $data = requestData();

        $email    = $data['email'] ?? null;
        $password = $data['clave'] ?? null;

        if (!is_string($email) || trim($email) === ''
            || !is_string($password) || $password === '') {
            Response::error('Faltan el email o la contrasena.', 400);
        }

        $session = $this->service->login(trim($email), $password);

        Response::success($session, 'Sesion iniciada. Guarda el token y mandalo en cada pedido.');
    }

    /**
     * GET /perfil   (hay que estar logueado)
     * Devuelve los datos del dueno del token.
     */
    public function profile()
    {
        // Si no hay token valido, este metodo corta aca con un 401.
        $tokenData = requireLogin();

        $user = $this->service->getProfile($tokenData['id']);

        Response::success($user);
    }
}
