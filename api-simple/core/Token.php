<?php

/**
 * Estas dos lineas traen las clases de la libreria firebase/php-jwt.
 *
 * ?Por que hacen falta? Porque las librerias guardan sus clases dentro
 * de un NAMESPACE (una especie de apellido) para que no choquen con las
 * nuestras. La clase completa se llama "Firebase\JWT\JWT".
 *
 * Con "use" le decimos a PHP: "cuando escriba JWT, me refiero a esa".
 * Es como agendar un contacto para no tener que marcar el numero entero.
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * CLASE TOKEN (JWT - JSON Web Token)
 * ==================================================================
 * EL PROBLEMA QUE RESUELVE
 *
 * HTTP no tiene memoria: cada pedido es independiente y el servidor no
 * se acuerda de quien sos. Entonces, despues del login, ?como sabemos
 * en el pedido siguiente que ya te habias logueado?
 *
 * LA SOLUCION
 *
 * Cuando el login sale bien, el servidor te da un TOKEN firmado.
 * Vos lo guardas y lo mandas en cada pedido, en una cabecera:
 *
 *     Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.eyJpZCI6MX0.xxxxx
 *
 * ------------------------------------------------------------------
 * ?COMO ES UN TOKEN POR DENTRO?
 *
 * Tres partes separadas por puntos:
 *
 *     encabezado . datos . firma
 *
 * !OJO! Las dos primeras partes NO estan encriptadas, solo estan en
 * Base64. Cualquiera las puede leer (proba pegando un token en jwt.io).
 *
 *   => NUNCA se guarda la contrasena adentro del token.
 *
 * Entonces, ?para que sirve? Para que nadie lo pueda MODIFICAR. Si un
 * usuario cambia "rol":"usuario" por "rol":"admin", la firma ya no
 * coincide, porque para recalcularla necesita la clave secreta que solo
 * tiene el servidor. El token queda invalido.
 *
 * ==================================================================
 * ?QUIEN HACE EL TRABAJO ACA?
 *
 * La libreria **firebase/php-jwt**, que es la que usa todo el mundo en
 * PHP para esto. La instalamos con Composer y quedo en vendor/.
 * Ella arma las tres partes, calcula la firma y controla el vencimiento.
 *
 * ?Y por que no lo escribimos nosotros, si son 30 lineas?
 * Porque en seguridad, "casi bien" es igual a "mal". Un detalle chico
 * (comparar la firma con == en vez de en tiempo constante, aceptar el
 * algoritmo "none", olvidarse de mirar el vencimiento) deja la puerta
 * abierta. Esa libreria la revisaron y la atacaron miles de personas
 * durante años. Nuestro codigo, no.
 *
 * REGLA GENERAL: la seguridad no se improvisa. Contrasenas, tokens y
 * encriptacion se hacen con herramientas ya probadas.
 *
 * ------------------------------------------------------------------
 * ESTA CLASE SIGUE EXISTIENDO IGUAL, Y ES A PROPOSITO.
 *
 * Podriamos llamar a JWT::encode() directamente desde el service, pero
 * entonces la libreria quedaria desparramada por todo el proyecto. Asi,
 * si algun dia cambiamos de libreria, se toca SOLO este archivo.
 *
 * A esto se le llama "envolver" una libreria (wrapper): nuestro codigo
 * le habla a Token, y Token es el unico que le habla a la libreria.
 * ==================================================================
 */
class Token
{
    /** Algoritmo de firma. HS256 = HMAC con SHA-256 y una clave secreta. */
    private const ALGORITHM = 'HS256';

    /**
     * Crea un token para un usuario que acaba de loguearse.
     */
    public static function create(User $user): string
    {
        // Los datos que van adentro del token. Solo lo necesario,
        // y NADA sensible (acordate de que se pueden leer).
        $payload = [
            'id'     => $user->getId(),
            'nombre' => $user->getName(),
            'rol'    => $user->getRole(),
            'iat'    => time(),                    // cuando se emitio
            'exp'    => time() + TOKEN_LIFETIME,   // cuando vence
        ];

        // Una sola linea: arma las tres partes y las firma.
        return JWT::encode($payload, SECRET_KEY, self::ALGORITHM);
    }

    /**
     * Lee el token que vino en el pedido y lo valida.
     *
     * Devuelve los datos del usuario si el token es valido,
     * o null si no hay token, esta vencido o esta adulterado.
     */
    public static function read(): ?array
    {
        $token = self::findInHeader();

        if ($token === null) {
            return null;
        }

        /**
         * ACA APARECE ALGO NUEVO: try / catch.
         *
         * Cuando la libreria encuentra un problema, no devuelve false:
         * LANZA UNA EXCEPCION, que es un error que corta la ejecucion.
         *
         *   try   -> "intenta hacer esto"
         *   catch -> "y si explota, agarralo aca y segui"
         *
         * Las que puede lanzar:
         *   SignatureInvalidException -> le tocaron la firma
         *   ExpiredException          -> se vencio
         *   BeforeValidException      -> todavia no es valido
         *   UnexpectedValueException  -> esta mal formado
         *
         * Como para nosotros todos esos casos significan lo mismo
         * ("este token no sirve"), los agarramos juntos y devolvemos null.
         *
         * Fijate tambien que le pasamos el algoritmo esperado (HS256).
         * Eso es importante: evita el ataque clasico de mandar un token
         * que dice "alg": "none" para que el servidor no verifique nada.
         */
        try {
            $payload = JWT::decode($token, new Key(SECRET_KEY, self::ALGORITHM));
        } catch (Exception $e) {
            return null;
        }

        // decode() devuelve un objeto (stdClass) y en el resto del
        // proyecto trabajamos con arreglos, asi que lo convertimos.
        return (array) $payload;
    }

    /**
     * Busca la cabecera "Authorization: Bearer xxxxx".
     *
     * Esto sigue siendo trabajo nuestro: la libreria se ocupa del token,
     * no de como viaja dentro del pedido HTTP.
     */
    private static function findInHeader(): ?string
    {
        $headers = getallheaders();

        // Las cabeceras pueden venir en mayusculas o minusculas
        // segun el servidor, asi que las normalizamos.
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                // Sacamos la palabra "Bearer " del principio.
                if (stripos($value, 'Bearer ') === 0) {
                    return trim(substr($value, 7));
                }
            }
        }

        return null;
    }
}
