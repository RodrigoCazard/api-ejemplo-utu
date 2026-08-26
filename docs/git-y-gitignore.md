# Git y el `.gitignore`

## Que es Git, en una frase

Git guarda el historial de cambios de un proyecto: quien cambio que linea,
cuando, y por que. Es lo que te permite volver atras si algo se rompe, y lo
que te permite compartir el proyecto con otros sin mandar la carpeta entera
por WhatsApp cada vez.

Este apunte no ensena a usar Git (eso se ve aparte); explica una sola cosa
puntual: **por que no todo lo que hay en la carpeta del proyecto se sube al
repositorio.**

## No todo archivo merece estar en git

Git esta pensado para guardar **codigo fuente**: lo que escribieron ustedes.
Hay dos tipos de archivos que NO deberian subirse nunca:

1. **Secretos** - el `.env`, con la clave del JWT y los datos de conexion a
   MySQL (usuario, contrasena). Si se sube, quedan visibles en el historial
   para siempre, incluso si despues los borran (`git log` los sigue
   mostrando).
2. **Archivos regenerables** - la carpeta `vendor/`, que Composer reconstruye
   solo con `composer install`. Subirla infla el repositorio con codigo de
   otra gente que ya esta descrito en `composer.json`.

Los datos en si (los productos, los usuarios) ya no viven en una carpeta
del proyecto: estan en la base MySQL, que tampoco es cosa de Git - se crea
una sola vez importando [database.sql](../api-completa/database.sql), y de ahi
en mas cada instalacion tiene la suya.

## El `.gitignore`

Es un archivo de texto en la raiz del proyecto que le dice a Git "estas
rutas, ni las mires". El de este proyecto:

```
.env
vendor/
```

Cada linea es un patron. `.env` ignora ese archivo (en cualquier carpeta,
por eso no lleva `/` adelante: asi ignora tanto `api-simple/.env` como
`api-completa/.env`); `vendor/` ignora esa carpeta entera, dondequiera que
aparezca.

**Importante:** el `.gitignore` solo funciona para archivos que Git
**todavia no conoce**. Si un archivo ya fue subido alguna vez, agregarlo aca
no lo borra del historial - hay que sacarlo aparte (`git rm --cached`). Por
eso conviene tener el `.gitignore` listo **antes** del primer `git add`.

## Que SI se sube

La contracara de lo de arriba: si `vendor/` no se sube, ?como sabe otra
persona que librerias necesita el proyecto? Por eso **si** se suben:

- `composer.json` y `composer.lock` - la lista de librerias y sus versiones
  exactas. Con eso, cualquiera reconstruye `vendor/` corriendo
  `composer install`.
- `.env.example` - la plantilla de variables de entorno, sin los valores
  reales (ver [variables-de-entorno.md](variables-de-entorno.md)).

## Como arrancar el repositorio de este proyecto

Si todavia no es un repositorio git (`git status` da error), se inicializa
una sola vez:

```bash
git init
git add .
git commit -m "Primer commit"
```

Con el `.gitignore` ya puesto ANTES de ese `git add .`, `.env` y `vendor/`
quedan afuera automaticamente - no hace falta acordarse de excluirlos a
mano cada vez.

## Resumen para no errarle

- `.gitignore` no borra archivos del disco: solo le dice a Git que los
  ignore al hacer `add`/`commit`.
- Si algo es secreto (contrasenas, claves) o se regenera solo (`vendor/`),
  no va a git.
- Si algo describe **como reconstruir** lo que no se sube (`composer.json`,
  `.env.example`, `database.sql`), si va a git.

