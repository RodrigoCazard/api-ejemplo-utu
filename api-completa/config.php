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
 * PHP no trae de fabrica un lector de .env (proyectos grandes usan
 * una libreria, "vlucas/phpdotenv"), pero el formato es tan simple
 * que alcanza con leer el archivo linea por linea. Eso es lo que
 * hace loadEnv() aca abajo.
 * ==================================================================
 */

/**
 * loadEnv(): LEE UN ARCHIVO .env LINEA POR LINEA
 * ==================================================================
 * Esta funcion es generica: no sabe nada de tokens ni de esta API en
 * particular, solo sabe leer archivos con formato "CLAVE=valor". Por
 * eso la pueden copiar y pegar tal cual al inicio de CUALQUIER otro
 * proyecto PHP que quieran armar, y ya van a tener soporte de .env.
 *
 * Lo que hace, paso a paso:
 *
 *   1. Si el archivo no existe, no hace nada (return) y listo. Asi
 *      el proyecto no explota si alguien todavia no creo su .env;
 *      simplemente van a valer los defaults que pusimos mas abajo.
 *
 *   2. file(...) lee el archivo y devuelve un arreglo con una linea
 *      por posicion. Los dos flags le piden que no incluya el salto
 *      de linea de cada renglon y que se salte las lineas vacias.
 *
 *   3. Por cada linea:
 *        - si empieza con "#", es un comentario: la ignoramos.
 *        - si no, la partimos en dos por el PRIMER "=" que aparece
 *          (el limite 2 de explode() es justamente para eso: si el
 *          VALOR tuviera un "=" adentro, no se rompe el parseo).
 *
 *   4. putenv() y $_ENV son dos formas que tiene PHP de guardar una
 *      variable de entorno. Las llenamos las DOS por compatibilidad:
 *      segun como este configurado el servidor, getenv() puede leer
 *      de una o de la otra. Guardando en ambas, siempre funciona.
 *
 * Despues de llamar a loadEnv() una sola vez (ver la linea de abajo),
 * cualquier archivo del proyecto puede leer esos valores con
 * getenv('SECRET_KEY'), o mejor, con el helper env() que definimos
 * a continuacion.
 *
 * @param string $path ruta al archivo .env
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

        $key   = trim($key);
        $value = trim($value);

        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// La llamamos UNA vez, apenas arranca la app, antes de leer ninguna
// variable. A partir de aca, getenv() ya "conoce" todo lo del .env.
loadEnv(__DIR__ . '/.env');

/**
 * env(): lee una variable del .env, y si no esta, usa un valor por
 * defecto. Asi ningun define() de abajo se rompe aunque falte el .env.
 */
function env(string $key, $default = null)
{
    $value = getenv($key);

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
define('DB_NAME', env('DB_NAME', 'utu_demo'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

