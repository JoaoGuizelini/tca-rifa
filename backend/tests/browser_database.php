<?php

// Browser fixtures use their own schema and storage; never migrate the app database.
$project = dirname(__DIR__, 2);
$settings = parse_ini_file($project.'/.env');
$database = getenv('BILHETE_BROWSER_DATABASE');
if (! $database || ! preg_match('/^bilhete_browser_test_[a-f0-9]{16}$/', $database)) {
    throw new RuntimeException('Nome inválido para banco de teste do navegador.');
}
$password = $settings['DB_ROOT_PASSWORD'];
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if (($argv[1] ?? '') === 'cleanup') {
    $pdo->exec('DROP DATABASE IF EXISTS `'.$database.'`');
    echo "Banco temporário removido.\n";
    exit(0);
}
if (($argv[1] ?? '') !== 'prepare') throw new RuntimeException('Operação inválida.');
$pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
foreach (['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $database, 'DB_USERNAME' => 'root', 'DB_PASSWORD' => $password,
    'APP_ENV' => 'local', 'CACHE_STORE' => 'file', 'SESSION_DRIVER' => 'file'] as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $_SERVER[$key] = $value;
}
$storage = getenv('LARAVEL_STORAGE_PATH');
if (! $storage) throw new RuntimeException('Storage de teste ausente.');
foreach (['app/private', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
    if (! is_dir($storage.'/'.$directory)) mkdir($storage.'/'.$directory, 0700, true);
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
foreach (App\Models\User::all() as $user) {
    $user->forceFill(['password' => 'TesteBrowser123'])->save();
}
echo "Banco temporário preparado.\n";
