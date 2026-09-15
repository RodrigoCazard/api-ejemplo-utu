<?php

// Prueba HTTP real con el index.php de cada API y una base SQLite temporal.
// php tests/http.php simple|completa
$application = $argv[1] ?? 'simple';
if (!in_array($application, ['simple', 'completa'], true)) exit(1);
$directory = dirname(__DIR__) . '/api-' . $application;
$temporaryFiles = [];
$process = null;
$checks = 0;
$exitCode = 0;

function temporaryFile(): string
{
    $file = tempnam(sys_get_temp_dir(), 'utu_http_');
    $GLOBALS['temporaryFiles'][] = $file;
    return $file;
}

function request(string $method, string $path, int $expected, ?array $body = null, string $authentication = ''): array
{
    global $port, $checks;
    $headers = "Content-Type: application/json\r\n" . $authentication;
    $context = stream_context_create(['http' => [
        'method' => $method, 'header' => $headers,
        'content' => $body === null ? '' : json_encode($body),
        'ignore_errors' => true, 'timeout' => 5,
    ]]);
    $response = file_get_contents("http://127.0.0.1:$port$path", false, $context);
    $responseHeaders = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : $http_response_header;
    preg_match('/HTTP\/\S+ (\d+)/', $responseHeaders[0] ?? '', $match);
    $status = (int) ($match[1] ?? 0);
    if ($status !== $expected) throw new RuntimeException("$method $path: esperaba $expected, llego $status");
    $json = json_decode($response, true);
    if (!is_array($json) || $json['ok'] !== ($status < 400)) throw new RuntimeException("Respuesta JSON incorrecta: $method $path");
    $checks++;
    return [$json['datos'] ?? null, $responseHeaders];
}

try {
    $databaseFile = temporaryFile();
    $routerFile = temporaryFile();
    $logFile = temporaryFile();
    $db = new PDO('sqlite:' . $databaseFile);
    $db->exec('PRAGMA foreign_keys = ON');
    $sql = file_get_contents($directory . '/database.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $sql = preg_replace('/CREATE DATABASE.*?;/s', '', $sql);
    $sql = preg_replace('/USE\s+utu_demo\s*;/i', '', $sql);
    $db->exec(str_replace('INT AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql));

    $router = '<?php' . "\n"
        . 'require_once ' . var_export($directory . '/config.php', true) . ';' . "\n"
        . 'require_once ' . var_export($directory . '/core/Database.php', true) . ';' . "\n"
        . '$db = new PDO(' . var_export('sqlite:' . $databaseFile, true) . ', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);' . "\n"
        . '$db->exec("PRAGMA foreign_keys = ON");' . "\n"
        . '(new ReflectionProperty(Database::class, "connection"))->setValue(null, $db);' . "\n"
        // Una clave exclusiva evita consumir el contador del usuario local.
        . '$_SERVER["REMOTE_ADDR"] = ' . var_export('test_' . bin2hex(random_bytes(8)), true) . ';' . "\n"
        . 'require ' . var_export($directory . '/index.php', true) . ';';
    file_put_contents($routerFile, $router);
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $message);
    $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket);
    $process = proc_open([PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $directory, $routerFile], [
        0 => ['pipe', 'r'], 1 => ['file', $logFile, 'a'], 2 => ['file', $logFile, 'a'],
    ], $pipes);
    fclose($pipes[0]);
    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $socket = @fsockopen('127.0.0.1', $port, $errno, $message, 0.1);
        if ($socket) { fclose($socket); $ready = true; break; }
        usleep(100000);
    }
    if (!$ready) throw new RuntimeException('El servidor PHP de prueba no inicio');

    request('GET', '/productos', 200);
    request('GET', '/productos?categoria[]=audio', 400);
    request('GET', '/productos/abc', 400);
    request('GET', '/productos/999999', 404);
    request('GET', '/perfil', 401);
    request('POST', '/login', 401, ['email' => 'admin@utu.edu.uy', 'clave' => 'incorrecta']);
    request('POST', '/registro', 400, ['nombre' => str_repeat('a', 101), 'email' => 'test@example.com', 'clave' => 'prueba123']);
    [$user] = request('POST', '/registro', 201, ['nombre' => 'Usuario prueba', 'email' => 'test@example.com', 'clave' => 'prueba123', 'rol' => 'admin']);
    if ($user['rol'] !== 'usuario') throw new RuntimeException('Registro HTTP permite admin');
    request('POST', '/registro', 400, ['nombre' => 'Usuario prueba', 'email' => 'test@example.com', 'clave' => 'prueba123']);
    [$session, $headers] = request('POST', '/login', 200, ['email' => 'test@example.com', 'clave' => 'prueba123']);
    $authentication = '';
    if ($application === 'simple') {
        $authentication = 'Authorization: Bearer ' . $session['token'] . "\r\n";
    } else {
        foreach ($headers as $header) {
            if (preg_match('/^Set-Cookie: ([^;]+)/i', $header, $match)) $authentication = 'Cookie: ' . $match[1] . "\r\n";
        }
        if (isset($session['token']) || $authentication === '') throw new RuntimeException('Login no coloca JWT exclusivamente en cookie');
    }
    request('GET', '/perfil', 200, null, $authentication);
    $input = ['nombre' => 'Producto HTTP', 'descripcion' => 'Prueba', 'precio' => 10.25, 'stock' => 3, 'categoria' => 'test'];
    request('POST', '/productos', 401, $input);
    foreach ([['nombre' => str_repeat('a', 151)], ['categoria' => str_repeat('a', 51)], ['precio' => '1e999'], ['stock' => 2147483648], ['descripcion' => str_repeat('a', 65536)], ['nombre' => []]] as $invalid) {
        request('POST', '/productos', 400, array_replace($input, $invalid), $authentication);
    }
    [$product] = request('POST', '/productos', 201, $input, $authentication);
    $id = $product['id'];
    request('POST', '/productos', 400, $input, $authentication);
    request('PATCH', "/productos/$id", 400, ['nombre' => 'Auriculares'], $authentication);
    request('PATCH', "/productos/$id", 400, ['precio' => '1e999'], $authentication);
    request('PATCH', "/productos/$id", 200, ['descripcion' => 'Cambio'], $authentication);
    request('POST', "/productos/$id/vender", 400, ['cantidad' => 1.5], $authentication);
    [$sale] = request('POST', "/productos/$id/vender", 200, ['cantidad' => 2], $authentication);
    if ($sale['producto']['stock'] !== 1 || $sale['total_a_pagar'] !== 20.5) throw new RuntimeException('Venta HTTP incorrecta');
    request('POST', "/productos/$id/vender", 400, ['cantidad' => 2], $authentication);
    request('DELETE', "/productos/$id", 403, null, $authentication);
    [$adminSession, $headers] = request('POST', '/login', 200, ['email' => 'admin@utu.edu.uy', 'clave' => 'admin123']);
    if ($application === 'simple') {
        $adminAuthentication = 'Authorization: Bearer ' . $adminSession['token'] . "\r\n";
    } else {
        foreach ($headers as $header) {
            if (preg_match('/^Set-Cookie: ([^;]+)/i', $header, $match)) $adminAuthentication = 'Cookie: ' . $match[1] . "\r\n";
        }
    }
    request('DELETE', "/productos/$id", 400, null, $adminAuthentication);
    request('PATCH', "/productos/$id", 200, ['stock' => 0], $adminAuthentication);
    request('DELETE', "/productos/$id", 200, null, $adminAuthentication);
    request('GET', "/productos/$id", 404);
    $db->exec('UPDATE productos SET descripcion = NULL WHERE id = 1');
    [$product] = request('GET', '/productos/1', 200);
    if ($product['descripcion'] !== '') throw new RuntimeException('Descripcion NULL rompe HTTP');
    echo "OK: api-$application, $checks requests HTTP, SQLite (no verifica MySQL nativo)\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FALLO: ' . $error->getMessage() . "\n");
    $exitCode = 1;
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    $db = null;
    foreach ($temporaryFiles as $file) if (is_file($file)) unlink($file);
}
exit($exitCode);
