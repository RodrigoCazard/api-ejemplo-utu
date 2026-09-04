<?php

/**
 * SERVICE DE AUTENTICACION
 * ==================================================================
 * Las reglas de "quien puede entrar al sistema":
 *
 *   - el email no se puede repetir
 *   - la contrasena se guarda como un hash, nunca como la escribieron
 *   - el que se registra solo es siempre rol "usuario"
 *   - una cuenta deshabilitada no puede entrar
 * ==================================================================
 */
class AuthService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Registrar una cuenta nueva.
     */
    public function register($name, $email, $password)
    {
        // REGLA: el email es unico.
        if ($this->userRepository->findByEmail($email) !== null) {
            Response::error('Ya existe una cuenta con ese email.', 400);
        }

        /**
         * REGLA: la contrasena se guarda como un HASH.
         * password_hash() crea una representacion de una sola direccion:
         * no se puede recuperar la contrasena original a partir del hash.
         * Si manana roban la base, no encuentran las claves en texto plano.
         *
         * Y ojo con esto: el rol lo ponemos nosotros ('usuario', dentro
         * del repository). Si lo aceptaramos del pedido, cualquiera se
         * registraria como admin.
         */
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $user = $this->userRepository->create($name, $email, $passwordHash);

        return $user->toArray();
    }

    /**
     * Iniciar sesion: verifica la contrasena y devuelve el token.
     */
    public function login($email, $password)
    {
        $user = $this->userRepository->findByEmail($email);

        /**
         * DETALLE DE SEGURIDAD:
         * el mensaje es el mismo si el email no existe o si la
         * contrasena esta mal. Si dijeramos "ese email no existe", le
         * estariamos regalando a un atacante la lista de que emails
         * estan registrados en el sistema.
         */
        if ($user === null || !$user->checkPassword($password)) {
            Response::error('Email o contrasena incorrectos.', 401);
        }

        // REGLA: una cuenta deshabilitada no entra.
        if (!$user->isActive()) {
            Response::error('Tu cuenta esta deshabilitada.', 403);
        }

        return [
            'token'   => Token::create($user),
            'usuario' => $user->toArray(),
        ];
    }

    /**
     * Los datos de un usuario.
     */
    public function getProfile($id)
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            Response::error('El usuario ya no existe.', 404);
        }

        return $user->toArray();
    }
}
