<?php

// php tests/database.php simple --sqlite
// php tests/database.php completa --sqlite
// Repetir con --mysql para probar el SQL nativo y las restricciones de MySQL.
// MySQL usa las credenciales del .env de esa API y una base temporal exclusiva.
// Nunca importa ni borra tablas de la base de la aplicacion.

$application = $argv[1] ?? 'simple';
$mode = $argv[2] ?? '--sqlite';
if (!in_array($application, ['simple', 'completa'], true)
    || !in_array($mode, ['--sqlite', '--mysql'], true)) {
    fwrite(STDERR, "Uso: php tests/database.php simple|completa --sqlite|--mysql\n");
    exit(1);
}
$directory = dirname(__DIR__) . '/api-' . $application;
require $directory . '/config.php';
require $directory . '/vendor/autoload.php';
require $directory . '/core/Database.php';

class HttpError extends RuntimeException
{
    public function __construct(public int $status, string $message)
    {
        parent::__construct($message);
    }
}

// Capturar las respuestas de error de los services sin terminar la prueba.
class Response
{
    public static function error($message, $status = 400, $errors = [])
    {
        throw new HttpError($status, $message);
    }
}

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function expectError(int $status, callable $action): void
{
    try {
        $action();
    } catch (HttpError $error) {
        check($error->status === $status, "Esperaba HTTP $status, llego {$error->status}");
        return;
    }
    throw new RuntimeException("Esperaba un error HTTP $status");
}

function sqliteSql(string $sql): string
{
    // Adaptacion explicita para probar PHP sin un servidor MySQL.
    // Este modo NO verifica la sintaxis ni los tipos propios de MySQL.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $sql = preg_replace('/CREATE DATABASE.*?;/s', '', $sql);
    $sql = preg_replace('/USE\s+utu_demo\s*;/i', '', $sql);
    return str_replace('INT AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
}

$server = null;
$temporaryDatabase = null;
$exitCode = 0;
try {
    $sql = file_get_contents($directory . '/database.sql');
    if ($mode === '--mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
        try {
            $server = new PDO($dsn, DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $error) {
            throw new RuntimeException('No se pudo conectar a MySQL. Revisar servidor, DB_PORT y credenciales del .env.');
        }
        $temporaryDatabase = 'utu_test_' . bin2hex(random_bytes(6));
        $server->exec(str_replace('utu_demo', $temporaryDatabase, $sql));
        $db = new PDO($dsn . ';dbname=' . $temporaryDatabase, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } else {
        $db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec(sqliteSql($sql));
    }

    // Los repositories usan esta conexion de prueba, nunca la base real.
    $connection = new ReflectionProperty(Database::class, 'connection');
    $connection->setValue(null, $db);
    foreach (glob($directory . '/models/*.php') as $file) require $file;
    require $directory . '/repositories/Repository.php';
    foreach (glob($directory . '/repositories/*.php') as $file) require_once $file;
    foreach (glob($directory . '/dtos/*.php') ?: [] as $file) require $file;
    foreach (glob($directory . '/validators/*.php') ?: [] as $file) require $file;
    require $directory . '/core/Token.php';
    require $directory . '/services/AuthService.php';
    require $directory . '/services/ProductService.php';

    $users = new UserRepository();
    $admin = $users->findByEmail('admin@utu.edu.uy');
    check($admin !== null && $admin->checkPassword('admin123') && $admin->getRole() === 'admin', 'Admin de prueba invalido');
    $student = $users->findByEmail('alumno@utu.edu.uy');
    check($student !== null && $student->checkPassword('alumno123'), 'Alumno de prueba invalido');
    $auth = new AuthService();
    $register = fn($email) => $application === 'simple'
        ? $auth->register('Usuario prueba', $email, 'prueba123')
        : $auth->register(new RegisterDTO(['nombre' => 'Usuario prueba', 'email' => $email, 'clave' => 'prueba123', 'rol' => 'admin']));
    $newUser = $register('test@example.com');
    check($newUser['rol'] === 'usuario' && !isset($newUser['clave_hash']), 'Registro expone hash o permite admin');
    expectError(400, fn() => $register('test@example.com'));
    $login = fn($email, $password) => $application === 'simple'
        ? $auth->login($email, $password)
        : $auth->login(new LoginDTO(['email' => $email, 'clave' => $password]));
    $session = $login('test@example.com', 'prueba123');
    $payload = (array) Firebase\JWT\JWT::decode($session['token'], new Firebase\JWT\Key(SECRET_KEY, 'HS256'));
    check($payload['id'] === $newUser['id'] && $payload['rol'] === 'usuario', 'JWT incorrecto');
    expectError(401, fn() => $login('test@example.com', 'incorrecta'));
    $db->exec("UPDATE usuarios SET activo = 0 WHERE email = 'test@example.com'");
    expectError(403, fn() => $login('test@example.com', 'prueba123'));
    check($auth->getProfile($newUser['id'])['email'] === 'test@example.com', 'Perfil incorrecto');
    expectError(404, fn() => $auth->getProfile(999999));

    $products = new ProductRepository();
    $service = new ProductService();
    check(count($service->getAll()) === 5 && count($service->getAll('audio')) === 1, 'Lista/filtro incorrecto');
    $db->exec('UPDATE productos SET descripcion = NULL WHERE id = 1');
    check($service->getById(1)['descripcion'] === '', 'Descripcion NULL rompe lectura');
    $input = ['nombre' => 'Producto prueba', 'descripcion' => 'Prueba', 'precio' => 10.25, 'stock' => 3, 'categoria' => 'test'];
    $create = fn() => $application === 'simple'
        ? $service->create($input['nombre'], $input['descripcion'], $input['precio'], $input['stock'], $input['categoria'])
        : $service->create(new CreateProductDTO($input));
    $product = $create();
    check($product['id'] > 5 && $products->findById($product['id'])->getPrice() === 10.25, 'Producto no persistido');
    expectError(400, $create);
    $update = fn($data) => $application === 'simple'
        ? $service->update($product['id'], $data)
        : $service->update($product['id'], new UpdateProductDTO($data));
    check($update(['descripcion' => 'Cambio'])['descripcion'] === 'Cambio', 'Update no persistido');
    expectError(400, fn() => $update(['nombre' => 'Auriculares']));
    expectError(404, fn() => $service->getById(999999));
    expectError(400, fn() => $service->delete($product['id']));
    $sale = $application === 'simple'
        ? $service->sell($product['id'], 2)
        : $service->sell($product['id'], new SellProductDTO(['cantidad' => 2]));
    check($sale['total_a_pagar'] === 20.5 && $sale['producto']['stock'] === 1, 'Venta/stock incorrectos');
    check(!$products->decreaseStock($product['id'], 2), 'Descuento permite vender stock inexistente');
    check($products->findById($product['id'])->getStock() === 1, 'Descuento fallido modifica stock');
    check($products->decreaseStock($product['id'], 1) && !$products->decreaseStock($product['id'], 1), 'Dos pedidos venden la ultima unidad');
    $service->delete($product['id']);
    check($products->findById($product['id']) === null, 'Delete no persistido');

    if ($application === 'simple') {
        // getallheaders() solo existe en HTTP; simular el header en esta prueba CLI.
        if (!function_exists('getallheaders')) {
            function getallheaders(): array
            {
                return $GLOBALS['testHeaders'] ?? [];
            }
        }
        require $directory . '/core/helpers.php';
        require $directory . '/controllers/VentaController.php';
        require $directory . '/controllers/ProductController.php';
        require $directory . '/services/VentaService.php';
        $GLOBALS['testHeaders'] = ['Authorization' => 'Bearer ' . Token::create($admin)];
        expectError(400, fn() => (new VentaController())->getSale('abc'));
        expectError(400, fn() => (new VentaController())->getSale(0));
        $GLOBALS['testHeaders'] = [];
        expectError(401, fn() => (new VentaController())->getSale(1));
        $controller = new ProductController();
        $limits = new ReflectionMethod(ProductController::class, 'validateStorageLimits');
        foreach ([['nombre' => str_repeat('a', 151)], ['categoria' => str_repeat('a', 51)], ['precio' => '1e999'], ['stock' => 2147483648], ['descripcion' => str_repeat('a', 65536)]] as $invalid) {
            expectError(400, fn() => $limits->invoke($controller, $invalid));
        }
        $limits->invoke($controller, ['nombre' => str_repeat('ñ', 150), 'categoria' => str_repeat('á', 50)]);
        check(true, 'Los limites deben contar caracteres UTF-8, no bytes');
        $sales = new VentaService();
        check($sales->getSale(1, 2, false)->getQuantity() === 1, 'Venta propia incorrecta');
        check($sales->getSale(1, 1, true)->getUserId() === 2, 'Admin no puede ver venta');
        expectError(403, fn() => $sales->getSale(1, 1, false));
        expectError(404, fn() => $sales->getSale(999999, 1, true));
        $reviews = new ReviewRepository();
        check(count($reviews->listReviewsByProduct(1)) === 1, 'Lista reviews incorrecta');
        check($reviews->listReviewsByProduct(2) === [], 'Lista vacia incorrecta');
        $review = $reviews->createReview(2, 2, 4, null);
        $stored = $db->query('SELECT * FROM reviews WHERE id = ' . $review->getId())->fetch();
        check($review->getDate() === $stored['fecha'] && $review->getComment() === null, 'Review no refleja la fila persistida');
        foreach (['reviews' => 'puntuacion', 'ventas' => 'cantidad'] as $table => $field) {
            foreach ([[999999, 1], [2, 999999]] as [$userId, $productId]) {
            try {
                $db->exec("INSERT INTO $table (usuario_id, producto_id, $field" . ($table === 'ventas' ? ', precio_unitario, total' : '') . ") VALUES ($userId, $productId, 1" . ($table === 'ventas' ? ', 1, 1' : '') . ')');
                throw new RuntimeException("$table permite una referencia inexistente");
            } catch (PDOException $error) {
                check($error->getCode() === '23000', "FK de $table no funciona");
            }
            }
        }
        $db->exec('UPDATE productos SET stock = 0 WHERE id = 1');
        if ($mode === '--mysql') {
            expectError(409, fn() => $service->delete(1));
        } else {
            try {
                $products->delete(1);
                throw new RuntimeException('Delete elimina el historial');
            } catch (PDOException $error) {
                check($error->getCode() === '23000', 'FK no protege historial');
            }
        }

        // Recrear la base de prueba usando solamente database.sql.
        $db->exec('DROP TABLE reviews');
        $db->exec('DROP TABLE ventas');
        $db->exec('DROP TABLE productos');
        $db->exec('DROP TABLE usuarios');
        $db->exec($mode === '--sqlite' ? sqliteSql($sql) : str_replace('utu_demo', $temporaryDatabase, $sql));
        foreach (['usuarios' => 2, 'productos' => 5, 'ventas' => 3, 'reviews' => 2] as $table => $count) {
            check((int) $db->query("SELECT COUNT(*) FROM $table")->fetchColumn() === $count, "database.sql no recrea $table con los ejemplos");
        }
        check($users->findByEmail('test@example.com') === null, 'Recrear la base conserva datos anteriores');
    } else {
        check(AuthValidator::validateRegister(['nombre' => str_repeat('a', 101), 'email' => 'a@b.com', 'clave' => 'prueba123']) !== [], 'Nombre de usuario desborda BD');
        foreach ([['nombre' => str_repeat('a', 151)], ['categoria' => str_repeat('a', 51)], ['precio' => '1e999'], ['stock' => 2147483648], ['descripcion' => str_repeat('a', 65536)]] as $invalid) {
            check(ProductValidator::validateCreateProduct(array_replace($input, $invalid)) !== [], 'Create acepta datos fuera de limites');
            check(ProductValidator::validateUpdateProduct(1, $invalid) !== [], 'Update acepta datos fuera de limites');
        }
    }
    echo "OK: api-$application, $checks comprobaciones, " . ($mode === '--mysql' ? 'MySQL nativo' : 'SQLite (PHP; no valida MySQL nativo)') . "\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FALLO: ' . $error->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if ($server !== null && $temporaryDatabase !== null) {
        $server->exec('DROP DATABASE IF EXISTS `' . $temporaryDatabase . '`');
    }
}
exit($exitCode);
