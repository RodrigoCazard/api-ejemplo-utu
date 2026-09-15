<?php

/**
 * CONFIGURACION
 * ==================================================================
 * Aca van los valores que pueden cambiar segun la computadora donde
 * corra la API. Usamos constantes (define) porque se pueden leer desde
 * cualquier archivo, sin tener que pasarlas de una funcion a otra.
 *
 * Por convencion, las constantes se escriben EN MAYUSCULAS.
 *
 * ------------------------------------------------------------------
 * ?QUE ES UN ".env" Y PARA QUE SIRVE?
 *
 * Es un archivo de texto con pares "CLAVE=valor", uno por linea, que
 * vive en la raiz del proyecto (mira ".env" al lado de este archivo).
 * Ahi van los datos que:
 *
 *   a) son SECRETOS (la clave para firmar tokens, la contrasena de
 *      la base de datos), o
 *   b) CAMBIAN segun la computadora (en tu maquina la base se llama
 *      distinto que en la del profesor, o en el servidor real).
 *
 * La regla es simple: el .env NUNCA se sube a git (mira el
 * .gitignore). Lo que si se sube es ".env.example", una copia sin
 * los valores reales, que le muestra a cualquiera que baje el
 * proyecto QUE variables necesita definir.
 *
 * ?Por que importa? Si la clave secreta estuviera escrita adentro
 * del codigo (como estaba antes aca mismo) y el codigo se sube a un
 * repositorio publico, cualquiera que lo vea puede fabricar tokens
 * JWT validos, incluso de administrador. Sacandola al .env, el
 * secreto vive solo en la computadora de cada uno.
 *
 * PHP no lee .env automaticamente. Esta version usa vlucas/phpdotenv
 * para cargarlo; api-simple conserva un lector propio para aprender.
 * ==================================================================
 */

// Composer tambien se carga aca para poder ejecutar config.php desde scripts CLI.
require_once __DIR__ . '/vendor/autoload.php';

// Los adaptadores por defecto leen/escriben $_SERVER y $_ENV.
// Agregamos getenv() SOLO como lector para respetar las variables del proceso
// (por ejemplo, las de Docker), sin escribirlas con putenv().
$environmentRepository = Dotenv\Repository\RepositoryBuilder::createWithDefaultAdapters()
    ->addReader(Dotenv\Repository\Adapter\PutenvAdapter::class)
    ->immutable()
    ->make();

// immutable() conserva las variables existentes. safeLoad() permite ejecutar
// sin archivo .env cuando la configuracion viene del servidor o de Docker.
Dotenv\Dotenv::create($environmentRepository, __DIR__)->safeLoad();

/** Lee la configuracion del entorno o del .env, con un valor por defecto. */
function env(string $key, $default = null)
{
    $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

    return $value === false || $value === '' ? $default : $value;
}

/**
 * APP_ENV indica en que tipo de entorno esta ejecutandose la API.
 *
 * development: entorno local para aprender y depurar. Puede habilitar
 *              la lista de endpoints y las cuentas de prueba.
 * production:  servidor publico. No publica endpoints de ayuda.
 *
 * Usamos production como valor por defecto seguro: si alguien se olvida de
 * configurar APP_ENV en un servidor, la API usa el modo mas seguro.
 */
$appEnv = strtolower((string) env('APP_ENV', 'production'));

// Solo aceptamos estos dos valores para detectar errores de escritura rapido.
if (!in_array($appEnv, ['development', 'production'], true)) {
    die('APP_ENV debe ser development o production.');
}

define('APP_ENV', $appEnv);

// Clave secreta para firmar los tokens. Sale del .env; jamas del codigo.
define('SECRET_KEY', env('SECRET_KEY'));

/**
 * FALLA RAPIDO (fail fast) si falta la clave o quedo con el valor de
 * ejemplo. Preferimos que la API no arranque a que arranque insegura
 * sin que nadie se de cuenta.
 */
if (!SECRET_KEY || SECRET_KEY === 'cambiame-por-una-clave-generada-al-azar') {
    die('Falta configurar SECRET_KEY en el archivo .env (mira .env.example).');
}

// Cuanto dura el token, en segundos (3600 = 1 hora). No es una sesion
// de PHP: aca no hay session_start() ni $_SESSION en ningun lado.
define('TOKEN_LIFETIME', (int) env('TOKEN_LIFETIME', 3600));

/**
 * Origen autorizado para que el frontend use la cookie con CORS.
 * env() lee FRONTEND_ORIGIN del .env o usa localhost:5173 por defecto.
 * rtrim(..., '/') quita una barra final para comparar origenes exactamente.
 * define() crea una constante accesible desde index.php.
 */
define('FRONTEND_ORIGIN', rtrim(env('FRONTEND_ORIGIN', 'http://localhost:5173'), '/'));

/**
 * Datos de conexion a MySQL. Los usa core/Database.php para armar
 * la conexion PDO. Mira database.sql para crear la base y las
 * tablas con estos mismos datos.
 */
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', (int) env('DB_PORT', 3306));
define('DB_NAME', env('DB_NAME', 'utu_demo'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));
