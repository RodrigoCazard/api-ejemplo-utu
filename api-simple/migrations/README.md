# Para que sirven las migraciones

Una migracion es un archivo que describe un cambio en una base existente:
por ejemplo, agregar una tabla o una columna sin tener que recrear toda la
base y perder sus datos.

Son utiles cuando una aplicacion ya tiene usuarios y datos que se deben
conservar, o cuando varias personas necesitan aplicar los mismos cambios
en sus bases. Habitualmente se numeran y se registra cuales se ejecutaron
para aplicarlas en orden y una sola vez.

Por ejemplo, un proyecto podria tener `001_crear_usuarios.sql` y luego
`002_agregar_telefono.sql`. Un framework puede ejecutar esos archivos
automaticamente; tener una carpeta llamada `migrations` no los ejecuta.

Para probar esta API usamos una base de prueba desde cero: borrar la base
anterior e importar `database.sql`, que incluye usuarios, productos, ventas,
reviews y sus datos de ejemplo. Los pasos estan en el [README de la API](../README.md#como-levantarla-localmente).

Esta carpeta contiene solamente esta explicacion, sin migraciones ejecutables.
