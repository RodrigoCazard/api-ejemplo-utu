# Verificar base y codigo existente

Para comprobar la carga de configuracion de completa con `phpdotenv`:

```powershell
php tests/environment.php
```

Usa archivos y procesos temporales. Comprueba valores entre comillas, referencias entre variables, prioridad de la configuracion del servidor, lectura de `getenv()` y funcionamiento sin `.env`. No modifica el `.env` real.

Desde la raiz del repositorio, con PHP, PDO y mbstring disponibles:

```powershell
php scripts/check-database.php simple
php scripts/check-database.php completa
```

Ese diagnostico usa el `.env` de cada API, consulta MySQL y comprueba las columnas necesarias. En simple tambien comprueba las cuatro claves foraneas de ventas/reviews. Es de solo lectura y no imprime credenciales.

Simple incluye tablas para usuarios, productos, ventas y reviews. Completa actualmente incluye usuarios y productos; ventas/reviews son parte del ejercicio de simple y no existen como endpoints en completa.

Para probar los repositories, services, JWT y las validaciones existentes sin MySQL:

```powershell
php tests/database.php simple --sqlite
php tests/database.php completa --sqlite
```

SQLite usa una base en memoria y una adaptacion del SQL. Comprueba el comportamiento PHP y las relaciones, pero no demuestra que el SQL nativo de MySQL se pueda importar.

Para probar tambien las rutas existentes por HTTP real:

```powershell
php tests/http.php simple
php tests/http.php completa
```

Estas pruebas inician un servidor PHP temporal con SQLite y lo detienen al terminar. Cubren registro, login, perfil, Bearer/cookie, creacion, modificacion, venta y eliminacion de productos, junto con errores de validacion y permisos. No usan ni modifican la base real.

Para verificar tambien la importacion original, las claves foraneas de MySQL y el error `409` al borrar un producto con historial:

```powershell
php tests/database.php simple --mysql
php tests/database.php completa --mysql
```

MySQL debe estar iniciado. Las pruebas usan las credenciales del `.env` de cada API para crear una base temporal `utu_test_...`; requieren permisos para crear y borrar esa base. La base configurada en `DB_NAME` no se modifica. Las pruebas de simple tambien recrean todas las tablas desde cero usando solamente `database.sql` y verifican los datos de ejemplo. No usan migraciones.

No completan ni habilitan las rutas faltantes del ejercicio, ni prueban el comportamiento del navegador. En `database.php` los services se prueban capturando sus errores en lugar de terminar PHP con `Response::error()`; `http.php` usa las respuestas reales de la API.
