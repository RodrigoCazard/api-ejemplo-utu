<?php

/**
 * CLASE DATABASE (conexion a MySQL)
 * ==================================================================
 * Se encarga de UNA sola cosa: darle a los repositories una conexion
 * PDO ya lista para usar, sin que cada uno tenga que saber como se
 * arma (host, usuario, contrasena, etc.).
 *
 * ------------------------------------------------------------------
 * ?QUE ES PDO?
 *
 * PDO (PHP Data Objects) es la forma estandar que tiene PHP de
 * hablar con una base de datos. La ventaja de usarla en vez de las
 * funciones viejas (mysqli_connect, mysqli_query...) es que el mismo
 * codigo sirve para MySQL, PostgreSQL, SQLite, etc.: solo cambia el
 * "DSN" (la primera linea de connection() de aca abajo).
 *
 * ------------------------------------------------------------------
 * ?POR QUE UNA SOLA CONEXION PARA TODO EL PEDIDO?
 *
 * Abrir una conexion a la base tiene un costo (viaja por red, la
 * base tiene que autenticarte). Si cada repository abriera la suya,
 * un pedido que use dos repositories (por ejemplo, "vender" mira el
 * producto Y podria loguear la venta) abriria la conexion dos veces
 * sin necesidad.
 *
 * self::$connection es una propiedad ESTATICA: existe una sola, la
 * comparten todos los repositories del pedido. connection() la crea
 * la PRIMERA vez que alguien la pide, y las siguientes veces devuelve
 * la misma (mira el "if" de abajo: si ya existe, no la vuelve a
 * abrir). Eso se llama patron SINGLETON.
 * ==================================================================
 */
class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

            self::$connection = new PDO($dsn, DB_USER, DB_PASSWORD, [
                /**
                 * Sin esto, PDO por defecto NO avisa los errores de SQL:
                 * una consulta mal escrita fallaria en silencio. Con
                 * ERRMODE_EXCEPTION, cualquier error de la base lanza una
                 * excepcion de PHP, igual que hace Token::read() con los
                 * errores del JWT.
                 */
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // Que fetch()/fetchAll() devuelvan arreglos asociativos
                // (['id' => 1, 'nombre' => '...']) y no objetos ni
                // arreglos numerados. Es el formato que ya usan los
                // repositories.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$connection;
    }
}
