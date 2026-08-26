# Mejoras opcionales para acercar el api completa a una API profesional

El api completa ya tiene una base correcta para aprender a construir una API:
separa controllers, validators, DTOs, services, repositories y models; utiliza
consultas preparadas; guarda las contrasenas con un hash; maneja la
autenticacion mediante JWT y centraliza los errores inesperados.

Este documento no describe tareas obligatorias. Es una hoja de ruta para el
estudiante que quiera seguir practicando y acercar el proyecto a una API de
produccion sin convertirlo de golpe en un sistema demasiado complejo.

## Prioridad 1: interpretar correctamente la entrada HTTP

Actualmente, si el cuerpo esta vacio o contiene un JSON mal escrito,
`Controller::getJsonBody()` devuelve `[]` en los dos casos. Seria mejor poder
distinguirlos.

Una version mas estricta deberia:

- Aceptar `application/json` en los endpoints que esperan JSON.
- Responder `415 Unsupported Media Type` cuando el tipo de contenido no sea el
  esperado.
- Detectar un JSON mal formado y responder `400 Bad Request`.
- Limitar el tamano maximo del cuerpo y responder `413 Payload Too Large`.
- Rechazar campos desconocidos o avisar claramente que no se utilizan.
- Validar longitudes maximas ademas de longitudes minimas.

Los maximos deberian coincidir con la base. Por ejemplo, si `nombre` es
`VARCHAR(150)`, el validator no deberia permitir mas de 150 caracteres. Un
dato demasiado largo es un error del cliente, no un error interno `500`.

## Prioridad 2: proteger las cookies contra CSRF

La cookie del JWT tiene `HttpOnly` y `SameSite=Lax`, lo cual es una buena base.
Sin embargo, una cookie se envia automaticamente en los pedidos y por eso los
endpoints que modifican datos tambien necesitan proteccion contra CSRF.

Una mejora educativa posible es crear un `CsrfMiddleware` para `POST`, `PATCH`
y `DELETE`. Este middleware podria:

1. Verificar que la cabecera `Origin` coincida con `FRONTEND_ORIGIN`.
2. Exigir un token CSRF en una cabecera como `X-CSRF-Token`.
3. Rechazar el pedido con `403 Forbidden` cuando la comprobacion falle.

Es importante comprender que CORS controla si un navegador puede leer una
respuesta. No deberia utilizarse como la unica proteccion para impedir que un
pedido malicioso se ejecute.

Referencia: [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html).

## Prioridad 3: vender stock de manera atomica

El proceso actual de venta hace tres pasos:

```text
leer el stock ? comprobar la cantidad ? guardar el nuevo stock
```

Si llegan dos ventas al mismo tiempo, ambas podrian leer el mismo stock antes
de que la otra lo cambie. A este problema se lo llama **condicion de carrera**.

Para un descuento sencillo se puede hacer una actualizacion condicional:

```sql
UPDATE productos
SET stock = stock - :cantidad
WHERE id = :id
  AND stock >= :cantidad;
```

La base realiza la comprobacion y el descuento como una sola operacion. Luego
el repository puede mirar `rowCount()` para saber si realmente se vendio.

Cuando una operacion necesita modificar varias tablas, por ejemplo stock,
venta y detalle de venta, conviene utilizar una transaccion y, cuando sea
necesario, un bloqueo como `SELECT ... FOR UPDATE`.

Referencia: [MySQL: InnoDB Locking Reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html).

## Prioridad 4: repetir las reglas criticas en la base

El validator ayuda al usuario y el service protege las reglas del negocio,
pero la base debe ser la ultima barrera contra datos imposibles.

Se podrian agregar restricciones como estas:

```sql
UNIQUE (nombre)
CHECK (precio >= 0)
CHECK (stock >= 0)
CHECK (rol IN ('usuario', 'admin'))
```

Si el nombre del producto debe ser unico, esa regla debe comprobarse al crear
y tambien al cambiar el nombre durante un update. La restriccion `UNIQUE` es
necesaria aunque el service consulte antes, porque dos pedidos simultaneos
podrian superar esa consulta previa.

Una duplicacion esperada deberia responder `409 Conflict`. Los errores de base
inesperados si deben seguir llegando al manejador general y convertirse en una
respuesta `500` sin mostrar informacion interna.

Tambien conviene decidir que significa el campo `activo`:

- Si se utiliza borrado logico, `DELETE` cambia `activo` a `0` y los listados
  normales excluyen esos registros.
- Si se utiliza borrado fisico, se puede eliminar el campo si no cumple otra
  funcion.

## Prioridad 5: reforzar la autenticacion

Las siguientes practicas permitirian continuar la parte de seguridad:

- Limitar los intentos de `/login` y `/registro` y responder `429 Too Many
  Requests` cuando se supera el limite.
- Aumentar la longitud minima de la contrasena y agregar una longitud maxima
  razonable, por ejemplo 128 caracteres.
- Exigir que `SECRET_KEY` sea una clave larga generada al azar.
- Agregar y verificar datos del JWT como emisor (`iss`) y audiencia (`aud`).
- Comprobar que el usuario continue activo y que conserve su rol en la base.
- Hacer que HTTPS sea obligatorio en produccion.
- Asegurar que la cookie siempre tenga `Secure` en produccion.

Cerrar sesion elimina la cookie del navegador, pero una copia robada del JWT
seguiria funcionando hasta su vencimiento. En un nivel posterior se puede
estudiar alguna de estas alternativas:

- Tokens de acceso con una vida mas corta.
- Una version de sesion almacenada en el usuario.
- Una lista de tokens revocados identificados mediante `jti`.
- Un sistema de access token y refresh token.

No es necesario implementar todas juntas para comprender la idea.

Referencias:

- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [OWASP REST Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/REST_Security_Cheat_Sheet.html)

## Prioridad 6: agregar pruebas automatizadas

Probar manualmente con navegador, Postman o Insomnia es util para aprender, pero una API
cercana a produccion deberia poder comprobarse automaticamente.

Un conjunto inicial de pruebas podria cubrir:

- Validaciones de registro, login y productos.
- Normalizacion y tipos de cada DTO.
- Registro y login correctos.
- Email y nombre de producto repetidos.
- Rutas que exigen autenticacion.
- Rutas exclusivas del administrador.
- Producto inexistente.
- Venta sin stock suficiente.
- Dos ventas simultaneas sobre el mismo stock.
- JSON mal formado y tipo de contenido incorrecto.
- Creacion y eliminacion de la cookie al iniciar y cerrar sesion.
- Respuestas `500` que no exponen consultas, contrasenas ni trazas internas.

Se pueden comenzar con pruebas unitarias de validators y DTOs, porque no
necesitan una base. Despues se agregan pruebas de integracion que recorran el
endpoint completo y utilicen una base exclusiva para testing.

## Mejoras para una segunda etapa

Estas tareas tambien son valiosas, pero pueden realizarse despues de las seis
prioridades anteriores.

### No usar `float` para dinero

Los numeros `float` pueden tener pequenas diferencias de precision. Para
precios se pueden guardar centesimos como enteros o trabajar con strings
decimales. La base ya utiliza `DECIMAL(10, 2)`, pero el DTO actualmente lo
convierte a `float`.

### Usar codigos HTTP mas precisos

Se pueden incorporar gradualmente:

| Codigo | Uso |
|---|---|
| `405` | La ruta existe, pero no admite ese metodo HTTP |
| `409` | Hay un conflicto, por ejemplo un email repetido |
| `413` | El cuerpo del pedido es demasiado grande |
| `415` | El tipo de contenido no es aceptado |
| `422` | El JSON es valido, pero sus datos no pasan la validacion |
| `429` | Se realizaron demasiados intentos |

Cuando se responde `405`, tambien se deberia enviar la cabecera `Allow` con
los metodos aceptados.

### Paginar los listados

`GET /productos` devuelve actualmente todos los registros. Con miles de
productos seria demasiado costoso. Se podrian aceptar parametros como:

```text
GET /productos?pagina=2&limite=20
```

El limite debe tener un maximo definido por el servidor para evitar que el
cliente solicite toda la tabla de una vez.

### Separar las reglas de negocio de las respuestas HTTP

La idea de las capas dice que el service no deberia conocer HTTP. Actualmente
los services llaman a `Response::error()`, por lo que todavia existe ese
acoplamiento.

En una evolucion del proyecto, el service podria lanzar excepciones propias:

```text
ProductNotFoundException
InsufficientStockException
DuplicateProductException
```

Un manejador global convertiria despues cada excepcion en `404`, `409` u otro
codigo HTTP. Asi, el mismo service podria utilizarse desde una API, una tarea
automatica o un programa de consola.

### Agregar cabeceras, logs y un identificador de pedido

En produccion conviene estudiar:

- `Cache-Control: no-store` para respuestas sensibles.
- `X-Content-Type-Options: nosniff`.
- HSTS cuando toda la aplicacion funciona mediante HTTPS.
- Un identificador distinto para cada pedido.
- Logs estructurados con fecha, ruta, metodo, usuario y ese identificador.
- Registro de eventos importantes como intentos de login, ventas y operaciones
  administrativas, sin guardar contrasenas ni tokens.

### Automatizar la calidad del codigo

Un nivel posterior podria incorporar:

- Namespaces y autoload PSR-4 mediante Composer.
- `declare(strict_types=1);`.
- Un formateador compatible con PSR-12.
- PHPStan o Psalm para analisis estatico.
- OpenAPI para documentar formalmente los endpoints.
- Migraciones de base en lugar de un unico archivo SQL.
- Integracion continua para ejecutar lint, pruebas y `composer audit`.

Tambien es importante mantener sincronizados `composer.json` y
`composer.lock`, para que todos instalen exactamente las mismas versiones con
`composer install`.

## Orden recomendado para practicar

Para no hacer todos los cambios al mismo tiempo, se recomienda este orden:

1. Mejorar la lectura y validacion del JSON.
2. Crear la proteccion CSRF.
3. Hacer atomica la venta de stock.
4. Agregar restricciones a la base y manejar los conflictos.
5. Incorporar rate limiting y reforzar JWT, cookies y HTTPS.
6. Escribir las pruebas automatizadas.
7. Recien despues agregar paginacion, excepciones de negocio, OpenAPI y las
   herramientas automaticas de calidad.

Cada mejora deberia hacerse en un cambio pequeno, probarse y documentarse
antes de comenzar la siguiente.

## Resumen para no errarle

- El api completa ya es una buena base educativa; estas mejoras son opcionales.
- Las primeras mejoras deberian ser JSON estricto, CSRF, stock atomico,
  restricciones de base, autenticacion mas resistente y pruebas.
- La seguridad se construye con varias barreras; no depende de una sola clase
  o validacion.


