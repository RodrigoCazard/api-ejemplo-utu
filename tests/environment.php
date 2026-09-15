<?php

// php tests/environment.php
// Prueba completa en procesos aislados y con archivos .env de prueba.
if (($argv[1] ?? '') === '--scenario') {
    $scenario = $argv[2];
    $directory = $argv[3];
    if ($scenario === 'getenv') {
        // Simular variables que solamente estan en el proceso, no en los arrays.
        foreach (['SECRET_KEY', 'DB_HOST'] as $key) unset($_ENV[$key], $_SERVER[$key]);
        putenv('SECRET_KEY=' . str_repeat('b', 64));
        putenv('DB_HOST=process_host');
    }
    require $directory . '/config.php';
    $expectedHost = $scenario === 'file' ? 'file_host' : 'process_host';
    $expectedSecret = str_repeat($scenario === 'file' ? 'a' : 'b', 64);
    $checks = [
        DB_HOST === $expectedHost,
        SECRET_KEY === $expectedSecret,
        TOKEN_LIFETIME === 3600,
        env('UNDEFINED_VALUE', 'default') === 'default',
    ];
    if ($scenario !== 'missing') {
        $checks[] = DB_NAME === $expectedHost . '_data';
        $checks[] = DB_PORT === 3355;
        $checks[] = FRONTEND_ORIGIN === 'http://localhost:5173';
        $checks[] = env('ZERO') === '0';
        $checks[] = env('EMPTY_VALUE', 'default') === 'default';
        // Los valores del archivo se escriben en los arrays, no con putenv().
        $checks[] = getenv('DB_NAME') === false;
    }
    if (in_array(false, $checks, true)) {
        fwrite(STDERR, "FALLO: configuracion $scenario\n");
        exit(1);
    }
    echo count($checks);
    exit(0);
}

$temporaryDirectory = sys_get_temp_dir() . '/utu_env_' . bin2hex(random_bytes(8));
mkdir($temporaryDirectory);
$exitCode = 0;
try {
    $applicationDirectory = dirname(__DIR__) . '/api-completa';
    $config = file_get_contents($applicationDirectory . '/config.php');
    $config = str_replace("__DIR__ . '/vendor/autoload.php'", var_export($applicationDirectory . '/vendor/autoload.php', true), $config);
    file_put_contents($temporaryDirectory . '/config.php', $config);
    $fixture = 'SECRET_KEY="' . str_repeat('a', 64) . '"' . "\n" . <<<'ENV'
APP_ENV=development
DB_HOST=file_host
DB_NAME="${DB_HOST}_data"
DB_PORT=3355
FRONTEND_ORIGIN="http://localhost:5173/" # quitar barra final
ZERO=0
EMPTY_VALUE=""
ENV;
    $total = 0;
    foreach (['file', 'external', 'getenv', 'missing'] as $scenario) {
        if ($scenario === 'missing') unlink($temporaryDirectory . '/.env');
        else file_put_contents($temporaryDirectory . '/.env', $fixture);
        $environment = getenv();
        foreach (['SECRET_KEY', 'APP_ENV', 'DB_HOST', 'DB_NAME', 'DB_PORT', 'DB_USER', 'DB_PASSWORD', 'TOKEN_LIFETIME', 'FRONTEND_ORIGIN', 'ZERO', 'EMPTY_VALUE', 'UNDEFINED_VALUE'] as $key) {
            unset($environment[$key]);
        }
        if (in_array($scenario, ['external', 'missing'], true)) {
            $environment['SECRET_KEY'] = str_repeat('b', 64);
            $environment['DB_HOST'] = 'process_host';
        }
        $process = proc_open([PHP_BINARY, __FILE__, '--scenario', $scenario, $temporaryDirectory], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes, null, $environment);
        fclose($pipes[0]);
        $result = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || !ctype_digit(trim($result))) {
            throw new RuntimeException("FALLO: escenario $scenario. " . $error);
        }
        $total += (int) $result;
    }
    echo "OK: phpdotenv, $total comprobaciones (archivo, entorno, getenv y sin .env)\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    $exitCode = 1;
} finally {
    foreach (['config.php', '.env'] as $file) {
        if (is_file($temporaryDirectory . '/' . $file)) unlink($temporaryDirectory . '/' . $file);
    }
    rmdir($temporaryDirectory);
}
exit($exitCode);
