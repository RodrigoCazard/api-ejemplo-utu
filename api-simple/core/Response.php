<?php

/**
 * CLASE RESPONSE (respuesta)
 * ------------------------------------------------------------------
 * Se encarga de una sola cosa: contestarle al cliente en formato JSON.
 *
 * Sus metodos son ESTATICOS. Eso quiere decir que se usan directamente
 * con :: y no hace falta crear un objeto:
 *
 *     Response::success($product);          <-- asi
 *     $r = new Response(); $r->success();   <-- NO hace falta
 *
 * ?Cuando conviene un metodo estatico? Cuando la clase no necesita
 * "recordar" nada. Response no guarda datos, solo hace un trabajo.
 */
class Response
{
    /**
     * Todo salio bien.
     *
     * @param mixed  $data    Lo que le devolvemos al cliente.
     * @param string $message Texto opcional.
     * @param int    $status  200 = OK, 201 = se creo algo nuevo.
     */
    public static function success($data = null, $message = '', $status = 200)
    {
        self::send([
            'ok'      => true,
            'mensaje' => $message,
            'datos'   => $data,
        ], $status);
    }

    /**
     * Algo salio mal.
     *
     * @param string $message Que paso.
     * @param int    $status  400, 401, 403, 404...
     * @param array  $errors  Lista de errores de validacion (opcional).
     */
    public static function error($message, $status = 400, $errors = [])
    {
        self::send([
            'ok'      => false,
            'mensaje' => $message,
            'errores' => $errors,
        ], $status);
    }

    /**
     * Manda el JSON y TERMINA el programa.
     *
     * Es privado porque solo lo usan success() y error(), que estan aca
     * adentro. Desde afuera nadie puede llamarlo (encapsulamiento).
     */
    private static function send($body, $status)
    {
        // El codigo de estado HTTP: le dice al cliente como salio todo.
        http_response_code($status);

        // Avisamos que lo que mandamos es JSON.
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // exit corta la ejecucion aca mismo: despues de responder,
        // no hay nada mas que hacer.
        exit;
    }
}
