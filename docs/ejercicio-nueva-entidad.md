# Ejercicio: agregar el endpoint de Ventas a `api-simple`

## Contexto

Ya hicieron el seguimiento de un endpoint de `productos`: entraron por
`index.php`, siguieron el camino hasta `ProductController`, de ahi a
`ProductService`, de ahi a `ProductRepository`, y volvieron con la
respuesta JSON. Ese recorrido -Controller -> Service -> Repository- es
siempre el mismo, cambie lo que cambie la entidad.

Ahora le toca a toda la clase construir, entre todos, una entidad
**nueva: `Venta`**. Cada `POST /productos/{id}/vender` descuenta stock
pero hoy no queda ningun registro de esa venta. Vamos a agregar eso.

**Todavia no vimos como disenar tablas**, asi que esa parte no es
parte del ejercicio: la tabla y el `Model` ya estan hechos. Este
ejercicio es **back basico**: cada uno de los **6 grupos** agrega **UN
metodo** (un endpoint) a los archivos compartidos `VentaRepository`,
`VentaService` y `VentaController`.

## Que ya esta hecho (no lo toquen)

| Que | Donde | Que tiene |
|---|---|---|
| La tabla `ventas` | `database.sql` | columnas + 3 filas de ejemplo |
| El Model `Venta` | `models/Venta.php` | getters de todos los campos, y `cancel()`: el unico cambio permitido despues de creada |

Si ya habian importado la base antes (una version sin la tabla
`ventas`), reimporten para que aparezca:

```bash
mysql -u root -p -e "DROP DATABASE utu_demo;"
mysql -u root -p < database.sql
```

`DROP DATABASE` borra la base entera, incluido lo que hayan probado a
mano. Los datos de ejemplo se cargan solos al reimportar.

### La tabla `ventas`

```sql
CREATE TABLE ventas (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT NOT NULL,
    producto_id      INT NOT NULL,
    cantidad         INT NOT NULL,
    precio_unitario  DECIMAL(10, 2) NOT NULL,
    total            DECIMAL(10, 2) NOT NULL,
    estado           VARCHAR(20) NOT NULL DEFAULT 'confirmada',
    fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ventas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id),

    CONSTRAINT fk_ventas_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
);
```

`usuario_id` y `producto_id` son **claves foraneas** (`FOREIGN KEY`):
guardan el `id` de una fila de otra tabla. Asi se relacionan dos tablas
en una base relacional. `estado` guarda `'confirmada'` o `'anulada'`:
una venta anulada no se borra (se pierde el historial), queda marcada.

## Como ya vienen armados los archivos compartidos

`models/Venta.php` ya esta completo. `VentaRepository.php`,
`VentaService.php` y `VentaController.php` ya existen, pero **vacios**:
son el esqueleto donde cada grupo agrega su metodo. Ya estan cargados
en `index.php` (los `require_once`) y los seis `case` del switch
estan puestos, pero **comentados**, esperando que cada grupo
descomente el suyo cuando termine.

```text
models/Venta.php            YA HECHO - no lo toquen
repositories/VentaRepository.php   agreguen su metodo aca, al final de la clase
services/VentaService.php          agreguen su metodo aca, al final de la clase
controllers/VentaController.php    agreguen su metodo aca, al final de la clase
index.php                          descomenten SOLO su case
```

**Regla de convivencia:** como varios grupos escriben en los mismos
tres archivos, cada uno agrega su metodo al final de la clase, sin
borrar ni reformatear el metodo de otro grupo. Si dos grupos necesitan
tocar el archivo al mismo tiempo, coordinense.

## Los seis endpoints (uno por grupo)

| # | Endpoint | Metodo en Controller | Metodo en Service | Metodo en Repository | Acceso |
|---|---|---|---|---|---|
| 1 | `GET /ventas` | `listSales()` | `getAll($userId, $isAdmin)` | `findAll($userId = null)` | Login. Un usuario normal ve solo las suyas; un admin las ve todas |
| 2 | `GET /ventas/{id}` | `getSale($id)` | `getById($id, $userId, $isAdmin)` | `findById($id)` | Login. Solo el dueno de esa venta o un admin (si no, `403`) |
| 3 | `POST /ventas` | `createSale()` | `create($userId, $productId, $quantity)` | `create(Venta $venta)` | Login. Recibe `producto_id` y `cantidad`; descuenta stock igual que `ProductService::sell()` |
| 4 | `POST /ventas/{id}/anular` | `cancelSale($id)` | `cancel($id, $userId, $isAdmin)` | `update(Venta $venta)` | Login. Devuelve el stock al producto. No se puede anular una venta ya anulada |
| 5 | `GET /productos/{id}/ventas` | `listSalesByProduct($productId)` | `getByProduct($productId)` | `findByProduct($productId)` | Solo admin. Historial de ventas de un producto puntual |
| 6 | `GET /ventas/resumen` | `salesSummary()` | `getSummary()` | `getTotals()` | Solo admin. Reporte agregado: cantidad de ventas confirmadas, cantidad anuladas, total vendido |

Los nombres de metodo de la tabla **no son opcionales**: son el
contrato para que los seis endpoints encajen en el mismo Controller,
Service y Repository sin pisarse. Si su grupo necesita un metodo
privado auxiliar ademas del de la tabla, esta bien, siempre que el
nombre publico sea ese.

**Endpoint 5** va en la seccion "Productos" del switch de `index.php`
(porque la ruta empieza con `/productos`), aunque el metodo viva en
`VentaController`. Ya esta el `case` comentado ahi, con una nota
explicando por que.

**Endpoint 6** necesita una consulta con `COUNT()` y `SUM()` en vez de
un `SELECT *`, algo que todavia no vieron en `ProductRepository`. Por
ejemplo:

```sql
SELECT
    COUNT(CASE WHEN estado = 'confirmada' THEN 1 END) AS cantidad_confirmadas,
    COUNT(CASE WHEN estado = 'anulada' THEN 1 END)    AS cantidad_anuladas,
    COALESCE(SUM(CASE WHEN estado = 'confirmada' THEN total END), 0) AS total_vendido
FROM ventas;
```

Este `SELECT` no devuelve filas de `ventas` (no hay que armar objetos
`Venta`): devuelve una sola fila con esos tres numeros, que el
`Repository` puede devolver tal cual como arreglo asociativo
(`$query->fetch()`), sin pasar por `buildVenta()`.

**Ojo con el orden en el switch:** `GET /ventas/resumen` y
`GET /ventas/{id}` tienen la misma forma (`$count === 2`), asi que el
`case` de `resumen` tiene que ir ANTES que el de `{id}` (si no,
"resumen" cae en el `case` del id y explota `validateId()`). Ya esta
ordenado asi en `index.php`, con un comentario explicandolo.

## Como se arma cada capa (recordatorio)

Miren `controllers/ProductController.php`, `services/ProductService.php`
y `repositories/ProductRepository.php` como espejo. Los metodos que mas
se parecen a los suyos:

- `create()` (crear venta) se parece a `ProductService::sell()`: valida
  que el producto exista y tenga stock, descuenta el stock, y ADEMAS
  guarda la fila en `ventas` (`sell()` hoy no la guarda).
- `cancel()` (anular) se parece a `ProductService::delete()` en que
  primero busca la venta y corta con `404` si no existe, y se parece a
  `sell()` en que tambien modifica el stock del producto (sumandolo de
  vuelta en vez de restarlo).
- `listSales()`/`getSale()` se parecen a `listProducts()`/`getProduct()`,
  con el agregado de mirar el usuario logueado para decidir que puede
  ver.
- `listSalesByProduct()` se parece a `findByName()` de
  `ProductRepository`: buscar por una columna que no es `id`, en vez de
  traer todo con `findAll()`.
- `salesSummary()` no tiene equivalente en Productos: es el unico
  endpoint que no arma objetos `Venta`, devuelve numeros calculados por
  la base (ver el `SELECT` con `COUNT()`/`SUM()` mas arriba).

Recordatorio de que hace cada capa:

- **Repository**: el UNICO lugar que escribe SQL. Todas las consultas
  con parametros con nombre (`:algo`), nunca texto pegado.
- **Service**: las reglas del negocio (existe el producto, hay stock,
  es el dueno, no esta ya anulada).
- **Controller**: recibe el pedido, valida que los datos vengan y
  tengan la forma correcta, le pasa la pelota al service, responde con
  `Response::success()`.

`requireLogin()` (en `core/helpers.php`) devuelve un arreglo con `id` y
`rol` del usuario logueado: es lo que van a usar para el `usuario_id`
al crear una venta, y para decidir "es el dueno" o "es admin".

## Que tiene que entregar cada grupo

1. Su metodo en `VentaRepository.php`, `VentaService.php` y
   `VentaController.php` (los tres, para que su endpoint funcione de
   punta a punta).
2. Su `case` descomentado en `index.php` (y solo el suyo).
3. Una fila agregada a la tabla de endpoints de
   `api-simple/README.md`, con su endpoint nuevo.
4. Prueba de que funciona: capturas de Postman (o coleccion
   exportada) con un caso de exito y un caso de error (por ejemplo el
   `404` o el `403` que le corresponda a su endpoint).

## Como probar mientras lo desarrollan

Levanten la API como ya lo vienen haciendo (ver
[`api-simple/README.md`](../api-simple/README.md#como-levantarla-localmente)).
La tabla `ventas` ya tiene 3 filas de ejemplo (usuario `alumno@utu.edu.uy`,
id `2`), asi que en cuanto tengan `findAll()`/`findById()` ya pueden
probar su `GET` con Postman o el navegador, sin esperar a que otro
grupo termine el `POST`.

## Como se va a corregir

- **Funciona de punta a punta**: el endpoint responde lo esperado,
  probado de verdad.
- **Respeta las capas**: SQL solo en el Repository, reglas de negocio
  solo en el Service, validacion de forma de los datos en el
  Controller.
- **Usa consultas preparadas** en el Repository (ver
  [`docs/seguridad-sqli-xss.md`](seguridad-sqli-xss.md)).
- **Codigos HTTP correctos**: `201` al crear, `404` si no existe,
  `403` si no es el dueno ni admin, `400` si la regla de negocio falla
  (ver [`docs/http-y-rest.md`](http-y-rest.md)).
- **No rompio el metodo de otro grupo** en los archivos compartidos.
- **README actualizado** con la fila del endpoint nuevo.
