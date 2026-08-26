# Variables de entorno (`.env`)

## El problema que resuelve

Un programa necesita datos que **no son parte del codigo**: una clave secreta,
la contrasena de una base de datos, la direccion de un servidor. Esos datos
tienen dos problemas si los escribis directo en el codigo:

1. **Cambian segun donde corra el programa.** En tu computadora la base se
   llama de una forma, en la del profesor de otra, y en un servidor real de
   otra distinta. Si el dato esta *adentro* del codigo, hay que editar el
   codigo cada vez que cambia de maquina.
2. **Algunos son secretos.** Si subis el codigo a GitHub (o a cualquier lado)
   y la clave esta escrita ahi adentro, ya no es secreta: la puede leer
   cualquiera que vea el repositorio.

La solucion: esos datos no van en el codigo, van en un archivo aparte que
**cada maquina tiene el suyo** y que **nunca se sube** al control de
versiones. Ese archivo es el `.env`.

## Como se usa en este proyecto

Hay dos archivos parecidos, y la diferencia entre ellos es la que importa:

| Archivo | ?Que tiene? | ?Se sube a git? |
|---|---|---|
| `.env.example` | los NOMBRES de las variables, con valores de ejemplo | **si** |
| `.env` | los valores REALES de esta maquina | **no** (esta en `.gitignore`) |

Cuando alguien baja el proyecto por primera vez, hace:

```bash
cp .env.example .env        # Linux/Mac
copy .env.example .env      # Windows
```

Y despues edita su `.env` con sus propios valores (por ejemplo, genera su
propia `SECRET_KEY`). El `.env.example` le sirvio de "receta": le dijo
exactamente que variables tenia que definir, sin revelarle ningun secreto de
otra persona.

## El formato

Un `.env` es texto plano, una variable por linea:

```
CLAVE=valor
OTRA_CLAVE=otro valor
# esto es un comentario, se ignora
```

Sin comillas, sin espacios alrededor del `=` (aunque si los hay, este
proyecto los saca solo). Nada mas.

## Como llegan esos valores al codigo

PHP no lee `.env` solo - hay que decirle como. En
[config.php](../api-completa/config.php)
hay una funcion `loadEnv()` que lo hace:

```php
loadEnv(__DIR__ . '/.env');
```

Ese archivo lee cada linea del `.env`, la separa en clave y valor, y los dos
los guarda con `putenv()`. A partir de ahi, **cualquier parte del proyecto**
puede leer esos valores con `getenv('SECRET_KEY')`, o con el ayudante que
tambien esta en `config.php`:

```php
env('SECRET_KEY')                 // el valor, o null si no esta
env('TOKEN_LIFETIME', 3600)       // el valor, o 3600 si no esta
env('FRONTEND_ORIGIN', 'http://localhost:5173')
env('APP_ENV', 'production')      // entorno; si falta, usa el mas seguro
```

Y `config.php` los convierte en las constantes que usa el resto del proyecto:

```php
define('SECRET_KEY', env('SECRET_KEY'));
```

## Desarrollo y produccion con `APP_ENV`

La misma aplicacion puede necesitar un comportamiento distinto segun donde
este ejecutandose. Para eso se usa:

```env
APP_ENV=development
```

- `development` habilita configuracion util para aprender y depurar.
  endpoints y las cuentas locales de prueba.
- `production` no publica endpoints de ayuda ni datos de prueba.
  datos.

`config.php` acepta unicamente esos dos valores. Si `APP_ENV` no esta definida,
elige `production`: ante una configuracion incompleta es preferible revelar
menos informacion.

Esta separacion no vuelve segura una ruta por ocultarla. Todas las rutas deben
seguir validando autenticacion, roles y datos de entrada. Ademas, las cuentas
de demostracion deben eliminarse o reemplazarse antes de publicar la API.

`loadEnv()` no tiene nada especifico de esta API - es una funcion generica de
20 lineas. **La pueden copiar tal cual al principio de cualquier otro
proyecto PHP** para tener soporte de `.env` sin instalar ninguna libreria.
(Proyectos grandes en general usan una ya hecha, `vlucas/phpdotenv`, que hace
lo mismo pero con mas casos cubiertos.)

## "Fallar rapido"

Fijate que `config.php` no se queda callado si falta la clave:

```php
if (!SECRET_KEY || SECRET_KEY === 'cambiame-por-una-clave-generada-al-azar') {
    die('Falta configurar SECRET_KEY en el archivo .env (mira .env.example).');
}
```

Es mejor que la API **no arranque** a que arranque funcionando pero insegura
sin que nadie se entere. A esta idea se le llama *fail fast* (fallar rapido):
si algo importante falta, avisar de inmediato y fuerte, no dejar que el
problema aparezca despues, escondido, en produccion.

## Resumen para no errarle

- `.env` ? secretos y config de esta maquina ? **nunca se sube a git**.
- `.env.example` ? la plantilla, sin secretos ? **si se sube**.
- En esta API tradicional, `index.php` se ejecuta y lee `.env` en cada
  peticion. Normalmente no hace falta reiniciar `php -S` despues de cambiarlo.
- Si borras el `.env` por accidente, no perdiste nada importante del codigo:
  lo volves a crear copiando `.env.example`.


