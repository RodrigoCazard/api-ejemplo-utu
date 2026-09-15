<?php

// Diagnostico de solo lectura: php scripts/check-database.php simple|completa
$application = $argv[1] ?? 'simple';
if (!in_array($application, ['simple', 'completa'], true)) {
    fwrite(STDERR, "Uso: php scripts/check-database.php simple|completa\n");
    exit(1);
}
$directory = dirname(__DIR__) . '/api-' . $application;
require $directory . '/config.php';
require $directory . '/core/Database.php';

try {
    $db = Database::connection();
} catch (PDOException $error) {
    fwrite(STDERR, "No se pudo conectar a MySQL para api-$application.\nRevisar que MySQL este iniciado y DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASSWORD en el .env de esa carpeta.\n");
    exit(1);
}

$expected = [
    'usuarios' => ['id', 'nombre', 'email', 'clave_hash', 'rol', 'activo'],
    'productos' => ['id', 'nombre', 'descripcion', 'precio', 'stock', 'categoria', 'activo'],
];
if ($application === 'simple') {
    $expected['ventas'] = ['id', 'usuario_id', 'producto_id', 'cantidad', 'precio_unitario', 'total', 'estado', 'fecha'];
    $expected['reviews'] = ['id', 'usuario_id', 'producto_id', 'puntuacion', 'comentario', 'fecha'];
}
$query = $db->prepare('SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema');
$query->execute([':schema' => DB_NAME]);
$actual = [];
foreach ($query->fetchAll() as $column) {
    $actual[$column['TABLE_NAME']][] = $column['COLUMN_NAME'];
}
$valid = true;
foreach ($expected as $table => $columns) {
    $missing = array_diff($columns, $actual[$table] ?? []);
    if ($missing !== []) {
        echo "FALLO: $table, faltan columnas: " . implode(', ', $missing) . "\n";
        $valid = false;
    } else {
        echo "OK: $table, columnas requeridas presentes.\n";
    }
}

if ($application === 'simple') {
    $query = $db->prepare('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = :schema AND REFERENCED_TABLE_NAME IS NOT NULL');
    $query->execute([':schema' => DB_NAME]);
    $keys = $query->fetchAll();
    foreach (['ventas', 'reviews'] as $table) {
        foreach (['usuario_id' => 'usuarios', 'producto_id' => 'productos'] as $column => $reference) {
            $found = false;
            foreach ($keys as $key) {
                if ($key['TABLE_NAME'] === $table && $key['COLUMN_NAME'] === $column
                    && $key['REFERENCED_TABLE_NAME'] === $reference && $key['REFERENCED_COLUMN_NAME'] === 'id') {
                    $found = true;
                }
            }
            echo ($found ? 'OK: ' : 'FALLO: ') . "$table.$column -> $reference.id\n";
            $valid = $valid && $found;
        }
    }
}
echo $valid ? "Estructura requerida presente. Esto no verifica endpoints ni todos los datos.\n"
    : "Para probar con el esquema actualizado, borrar la base de prueba e importar database.sql desde cero (ver README de la API). Esto elimina los datos anteriores.\n";
exit($valid ? 0 : 1);
