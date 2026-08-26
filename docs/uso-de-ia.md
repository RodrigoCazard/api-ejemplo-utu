# Como usar la IA para aprender a programar (y como no)

Este proyecto se armo ayudado por IA, asi que conviene ser honestos sobre
esto: es una herramienta muy buena, y tambien muy facil de usar mal. La
diferencia entre las dos cosas no es "usarla o no usarla" - es **como**.

## La prueba que importa: ?podes explicarlo sin la IA?

Si la IA te arreglo un error o te escribio una funcion y no podes explicar,
cerrando la ventana del chat, **que hace cada linea y por que**, no aprendiste
nada: solo copiaste. La proxima vez que aparezca un error parecido vas a
estar en el mismo lugar que hoy.

Antes de dar por terminado un ejercicio con ayuda de IA, preguntate:

- ?Puedo explicar esto en el pizarron sin mirar la pantalla?
- ?Se decir **por que** se hizo asi y no de otra forma?
- Si manana aparece un error parecido en otro archivo, ?lo reconozco solo?

Si la respuesta a alguna es "no", el paso siguiente no es pedirle otra cosa a
la IA: es volver atras y entender lo que ya te dio.

## Como preguntar bien

Una mala pregunta da una mala respuesta, aunque la IA sea buena. Comparacion:

| Mal | Bien |
|---|---|
| "no me anda, arreglalo" | "esperaba que `/productos/3` devuelva el producto 3, pero me da 404. Mande un GET con Postman. Aca esta el `Router.php` y el `routes.php`" |
| "hace un CRUD de categorias" | "quiero agregar `Category` copiando la misma estructura que `Product` (model, repository, service, controller). ?Que archivos tengo que tocar y en que orden?" |
| "por que esto esta mal" (pegando 200 lineas) | "en este metodo `sell()`, ?por que se valida la cantidad DESPUES de buscar el producto y no antes?" |

La regla general: **cuanto mas especifico el contexto y mas concreta la
pregunta, mas util (y mas corta) la respuesta.** Si tenes que pegar el
proyecto entero para que se entienda tu pregunta, probablemente la pregunta
todavia es demasiado grande - conviene partirla.

## No aceptar todo como verdad

La IA se equivoca, y se equivoca con la misma seguridad con la que acierta -
no hay ninguna senal en el tono de la respuesta que te avise "che, esto no
estoy seguro". Por eso:

- **Corre el codigo antes de asumir que funciona.** Una explicacion que
  suena razonable no reemplaza probarlo.
- **Si algo te suena raro, decilo.** "?Estas seguro de que `==` compara igual
  que `===` aca?" es una pregunta mejor que asumir que la IA ya lo penso.
- **Pedi la fuente cuando importa.** Sobre todo en seguridad (?por que esta
  libreria y no escribirlo a mano? ver [seguridad-sqli-xss.md](seguridad-sqli-xss.md)
  y el comentario de [Token.php](../api-completa/core/Token.php)) conviene poder
  contrastar con la documentacion oficial, no solo con lo que dijo el chat.
- **Desconfia mas cuando la respuesta te conviene.** Si le preguntas "?esta
  bien esto que hice?" es facil que la respuesta suene a que si. Preguntar
  "?que le encontras mal a esto?" suele sacar mas jugo.

## Si algo no se sabe: volver a preguntar, no inventar

Ni la IA ni ustedes tienen que saber todo de una. Si una respuesta usa una
palabra que no conocen (?que es "idempotente"? ?que es un "wrapper"?), la
salida correcta es **volver a preguntar ahi mismo** ("explicamelo con un
ejemplo", "no entendi esa palabra") antes de seguir. Seguir de largo sin
entender un paso arma una torre sobre una base que no existe: el problema
aparece despues, mas dificil de encontrar.

Y cuando la duda es sobre algo importante o que da vueltas (contrasenas,
seguridad, "?esto es una buena practica de verdad?"), vale la pena
**contrastar con otra fuente**: la documentacion oficial de PHP
(<https://www.php.net/manual/es/>), la de la libreria que esten usando (por
ejemplo, [firebase/php-jwt en GitHub](https://github.com/firebase/php-jwt)),
o preguntarle al profesor. Ninguna fuente sola -tampoco la IA- es infalible;
cruzar dos es lo que da confianza real.

## Lo que la IA no puede hacer por ustedes

- **No puede rendir el examen por ustedes.** Puede ayudar a preparar, pero el
  entendimiento tiene que quedar en la cabeza de cada uno.
- **No reemplaza correr y probar el codigo.** "Le pregunte a la IA y me dijo
  que andaba" no es lo mismo que "lo corri y anduvo".
- **No sabe el contexto de la clase.** No sabe que explico el profesor la
  semana pasada, ni que forma de resolverlo se espera en este curso puntual.
  Ante la duda, la palabra del profesor pesa mas que la de la IA.

## Resumen para no errarle

- Si no podes explicarlo sin la IA delante, todavia no lo sabes.
- Preguntas especificas con contexto real dan mejores respuestas que
  preguntas grandes y vagas.
- Corre el codigo; no confies en que "suena bien".
- Si algo no se entiende, volve a preguntar ahi mismo - no sigan de largo
  con una base que no entendieron.
- Para temas importantes (seguridad, buenas practicas), contrasten con la
  documentacion oficial o con el profesor, no se queden con una sola fuente.

