# HTTP y REST

## Que es HTTP

Es el protocolo (el idioma acordado) con el que un cliente (un navegador, una
app, Postman) le habla a un servidor por internet. Cada pedido tiene siempre
la misma forma: un **metodo**, una **direccion**, y opcionalmente un
**cuerpo** con datos. Cada respuesta tiene un **codigo de estado** y
opcionalmente un cuerpo.

HTTP **no tiene memoria**: cada pedido es independiente del anterior. El
servidor no sabe por si solo que dos pedidos vienen de la misma persona. Por
eso se usa un JWT: en `api-simple` viaja en `Authorization` y en `api-completa`
viaja en una cookie HttpOnly (ver [Token.php](../api-completa/core/Token.php)).

## Los metodos (verbos)

Cada metodo comunica una *intencion* distinta sobre el mismo recurso:

| Metodo | Intencion | Ejemplo en esta API |
|---|---|---|
| `GET` | leer, sin cambiar nada | `GET /productos` |
| `POST` | crear algo nuevo | `POST /productos` |
| `PUT` | reemplazar/modificar algo que ya existe | `PUT /productos/3` |
| `DELETE` | borrar | `DELETE /productos/3` |

Un detalle que suele confundir al principio: **la direccion puede ser
identica** y lo que cambia es el metodo.

```
GET    /productos      -> listar
POST   /productos      -> crear
```

No existe `/crearProducto`. Eso es justamente la idea de REST (ver mas
abajo): las direcciones nombran **recursos** (sustantivos: "productos"), y
los metodos dicen **que hacer** con ellos (verbos).

### Idempotencia (una palabra que vale la pena conocer)

Un metodo es *idempotente* si pedirlo una vez o pedirlo diez veces seguidas
da el mismo resultado final. `GET`, `PUT` y `DELETE` son idempotentes: pedir
`DELETE /productos/3` diez veces termina igual que pedirlo una sola vez (la
primera lo borra, las siguientes ya no tienen nada para borrar). `POST` **no**
es idempotente: mandarlo diez veces crea diez productos.

## Los codigos de estado

El primer digito ya dice la categoria:

| Rango | Significa |
|---|---|
| `2xx` | salio bien |
| `4xx` | error del que pidio (mando algo mal) |
| `5xx` | error del servidor (se rompio algo del lado de aca) |

Los que usa esta API:

| Codigo | Nombre | Cuando |
|---|---|---|
| `200` | OK | salio todo bien |
| `201` | Created | se creo algo (`POST` que tuvo exito) |
| `204` | No Content | salio bien, no hay nada que devolver (lo usa el `OPTIONS` de CORS) |
| `400` | Bad Request | los datos vinieron mal, o no se cumple una regla |
| `401` | Unauthorized | no sabemos quien sos: falta la sesion o vencio el JWT |
| `403` | Forbidden | sabemos quien sos, pero no tenes permiso |
| `404` | Not Found | esa direccion, o ese registro, no existe |

**401 vs 403 es la confusion mas comun:** 401 es una pregunta de
*autenticacion* (?quien sos?); 403 es una pregunta de *autorizacion* (ya se
quien sos, ?te dejo?). Ejemplo real del proyecto: pedir `DELETE /productos/3`
sin iniciar sesion da 401; pedirlo con la sesion de un usuario normal (no
admin) da 403.

## Que es REST

REST es un **estilo** para disenar APIs, no una tecnologia ni una libreria.
La idea central: cada direccion representa un **recurso** (una cosa: un
producto, un usuario), y se opera sobre ese recurso usando los metodos HTTP
que ya existen, en vez de inventar una direccion distinta por cada accion.

```
GET    /productos       listar
GET    /productos/3     ver uno
POST   /productos       crear
PUT    /productos/3     modificar
DELETE /productos/3     borrar
```

Esas cinco operaciones sobre un mismo recurso se llaman **CRUD**
(Create, Read, Update, Delete), y son tan comunes que Laravel les da nombres
estandar a los metodos del controlador - los mismos que usa este proyecto a
proposito:

| Metodo del controller | Corresponde a |
|---|---|
| `index()` | listar (`GET /productos`) |
| `show()` | ver uno (`GET /productos/3`) |
| `store()` | crear (`POST /productos`) |
| `update()` | modificar (`PUT /productos/3`) |
| `destroy()` | borrar (`DELETE /productos/3`) |

### ?Y las acciones que no son CRUD?

No todo en un sistema es "guardar un dato". `POST /productos/3/vender` no
encaja en el CRUD (vender no es "crear", es una operacion con reglas propias:
descuenta stock, calcula un total). La convencion en esos casos es agregar un
verbo a la direccion, siempre despues del recurso al que pertenece:
`/productos/{id}/vender`, `/pedidos/{id}/cancelar`, etc.

## Como entra un pedido a esta API

```
1. El cliente manda:  GET /productos/3
                            ?
2. index.php lee $_SERVER['REQUEST_METHOD'] (GET) y la URL (/productos/3)
                            ?
3. routes.php ya armo la tabla de direcciones conocidas
                            ?
4. Router busca cual coincide y ejecuta el middleware de la ruta
                            ?
5. El controller llama al validator, al DTO y al service que correspondan
                            ?
6. El controller responde con Response::success(...) ? codigo 200 + JSON
```

Ver [index.php](../api-completa/index.php) y
[core/Router.php](../api-completa/core/Router.php) para el codigo real de cada paso.

## Resumen para no errarle

- La direccion nombra **que** cosa; el metodo dice **que hacer** con ella.
- `GET` nunca deberia cambiar datos (ni crear, ni borrar, ni modificar).
- 401 = no se quien sos. 403 = se quien sos, pero no podes.
- Si una accion no es un CRUD tipico, se agrega como verbo despues del
  recurso: `/recurso/{id}/accion`.

