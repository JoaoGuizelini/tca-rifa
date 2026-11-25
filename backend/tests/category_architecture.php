<?php

// Default: SQLite in memory. MySQL is allowed only in a separate named test database.
// Testing mode skips CSRF; this suite checks authorization and category behavior.
$mysqlTestDatabase = getenv('BILHETE_TEST_MYSQL_DATABASE');
if ($mysqlTestDatabase && ! preg_match('/^bilhete_architecture_test_[a-f0-9]{16}$/', $mysqlTestDatabase)) {
    throw new RuntimeException('Nome do banco MySQL de teste inválido.');
}
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => $mysqlTestDatabase ? 'mysql' : 'sqlite', 'DB_DATABASE' => $mysqlTestDatabase ?: ':memory:', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array'] as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
});

use App\Models\Category;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function http(string $method, string $uri, ?User $user = null, array $data = [], int $expected = 200, array $files = [], bool $raw = false)
{
    global $kernel;
    app('session')->driver()->flush();
    Auth::forgetGuards();
    if ($user) {
        Auth::guard()->setUser($user);
    }
    $server = ['HTTP_ACCEPT' => 'application/json'];
    if (! $files) $server['CONTENT_TYPE'] = 'application/json';
    $request = Request::create($uri, $method, $files ? $data : [], [], $files, $server, $files ? null : json_encode($data, JSON_THROW_ON_ERROR));
    $request->setUserResolver(fn () => $user);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    check($response->getStatusCode() === $expected,
        "$method $uri: esperado $expected, recebido ".$response->getStatusCode().' '.$response->getContent());

    return $raw ? $response : json_decode($response->getContent(), true);
}

check($mysqlTestDatabase
    ? config('database.default') === 'mysql' && config('database.connections.mysql.database') === $mysqlTestDatabase
    : config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:', 'Banco de teste não isolado.');
check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migração falhou.');
check(Artisan::call('db:seed', ['--force' => true]) === 0, 'Seeder falhou.');
$admin = User::whereHas('role', fn ($query) => $query->where('name', 'administrador'))->firstOrFail();
$participant = User::whereHas('role', fn ($query) => $query->where('name', 'participante'))->firstOrFail();
$organizer = User::whereHas('role', fn ($query) => $query->where('name', 'organizador'))->firstOrFail();

http('GET', '/api/categorias', expected: 401);
foreach ([$participant, $organizer, $admin] as $user) {
    $categories = http('GET', '/api/categorias', $user);
    check(count($categories) === 3, 'Catálogo de categorias incompleto.');
    check(array_keys($categories[0]) === ['id', 'name'], 'Contrato JSON alterado.');
}
foreach ([$participant, $organizer] as $user) {
    http('POST', '/api/admin/categorias', $user, ['name' => 'Sem permissão'], 403);
    http('POST', '/api/admin/categorias/1', $user, ['name' => 'Sem permissão'], 403);
    http('DELETE', '/api/admin/categorias/1', $user, expected: 403);
}
$suspended = clone $admin;
$suspended->suspended = true;
http('GET', '/api/categorias', $suspended, expected: 403);

http('POST', '/api/admin/categorias', $admin, [], 422);
http('POST', '/api/admin/categorias', $admin, ['name' => str_repeat('a', 61)], 422);
http('POST', '/api/admin/categorias', $admin, ['name' => 'Esporte'], 422);
http('POST', '/api/admin/categorias', $admin, ['name' => 'Livros e Arte', 'id' => 999], 204);
$created = Category::where('name', 'Livros e Arte')->firstOrFail();
check($created->id !== 999, 'Atributo extra aceito indevidamente.');
http('POST', '/api/admin/categorias/'.$created->id, $admin, ['name' => 'Livros e Arte'], 204);
http('POST', '/api/admin/categorias/'.$created->id, $admin, ['name' => 'Esporte'], 422);
http('POST', '/api/admin/categorias/'.$created->id, $admin, ['name' => 'Literatura'], 204);
check($created->fresh()->name === 'Literatura', 'Atualização falhou.');
http('POST', '/api/admin/categorias/99999', $admin, ['name' => 'Inexistente'], 404);
http('DELETE', '/api/admin/categorias/1', $admin, expected: 409);
check(Category::findOrFail(1)->campaigns()->count() === 1, 'Relacionamento ou proteção da categoria em uso falhou.');
http('DELETE', '/api/admin/categorias/'.$created->id, $admin, expected: 204);
http('DELETE', '/api/admin/categorias/'.$created->id, $admin, expected: 204);
check(Category::find($created->id) === null, 'Exclusão falhou.');

$service = app(CategoryService::class);
check($service->all([], ['field' => 'name', 'value' => 'Esporte'])->count() === 1, 'Filtro do BaseRepository falhou.');
check($service->allPaginate([], [], 'name', 2)->count() === 2, 'Paginação falhou.');
check($service->find(1, ['campaigns'])->relationLoaded('campaigns'), 'Carregamento de relacionamentos falhou.');

echo "OK: rotas HTTP, papéis, conta suspensa, validação, CRUD, contrato JSON, relacionamentos, filtro e paginação.\n";
