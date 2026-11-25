<?php

// Internal worker: writes only to the disposable MySQL schema of the test suite.
$database = getenv('BILHETE_TEST_MYSQL_DATABASE');
if (! $database || ! preg_match('/^bilhete_architecture_test_[a-f0-9]{16}$/', $database)) {
    throw new RuntimeException('Worker exige banco MySQL de teste isolado.');
}
foreach (['DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $database, 'APP_ENV' => 'testing', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::findOrFail((int) $argv[1]);
$service = app(App\Services\ReservationService::class);
echo "READY\n";
flush();
$deadline = microtime(true) + 15;
while (! is_file($argv[4])) {
    if (microtime(true) > $deadline) throw new RuntimeException('Timeout na barreira de concorrência.');
    usleep(10000);
    clearstatcache();
}
try {
    $id = $service->store((int) $argv[2], [(int) $argv[3]], $user);
    echo json_encode(['status' => 200, 'id' => $id])."\n";
} catch (Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $error) {
    echo json_encode(['status' => $error->getStatusCode()])."\n";
}
