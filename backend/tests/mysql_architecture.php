<?php

// Creates and deletes only a uniquely named test schema; preserves the app DB.
// Defaults to this project's local Compose root account. No passwords are printed.
$project = dirname(__DIR__, 2);
$settings = is_file($project.'/.env') ? parse_ini_file($project.'/.env') : [];
$username = getenv('BILHETE_TEST_MYSQL_USERNAME') ?: 'root';
$password = getenv('BILHETE_TEST_MYSQL_PASSWORD') ?: ($settings['DB_ROOT_PASSWORD'] ?? null);
$host = getenv('BILHETE_TEST_MYSQL_HOST') ?: '127.0.0.1';
$port = getenv('BILHETE_TEST_MYSQL_PORT') ?: '3306';
if ($password === null) {
    throw new RuntimeException('Configure BILHETE_TEST_MYSQL_PASSWORD ou o .env da raiz usado pelo Compose.');
}
$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'bilhete_architecture_test_'.bin2hex(random_bytes(8));
if (! preg_match('/^bilhete_architecture_test_[a-f0-9]{16}$/', $database)) throw new RuntimeException('Nome inválido.');
$pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    foreach (['BILHETE_TEST_MYSQL_DATABASE' => $database, 'DB_USERNAME' => $username, 'DB_PASSWORD' => $password, 'DB_HOST' => $host, 'DB_PORT' => $port] as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
    if (($argv[1] ?? '') === 'simplification') {
        require __DIR__.'/simplification_migration.php';
    } else {
        require __DIR__.'/application_architecture.php';
        require __DIR__.'/reservation_concurrency.php';
        require __DIR__.'/permission_architecture.php';
    }
    echo "OK: suíte completa em schema MySQL isolado.\n";
} finally {
    $pdo->exec('DROP DATABASE `'.$database.'`');
}
