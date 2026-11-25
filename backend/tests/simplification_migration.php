<?php

// Upgrade existing data in an isolated DB, never in the configured application DB.
$mysqlDatabase = getenv('BILHETE_TEST_MYSQL_DATABASE');
if ($mysqlDatabase && ! preg_match('/^bilhete_architecture_test_[a-f0-9]{16}$/', $mysqlDatabase)) {
    throw new RuntimeException('Banco de teste inválido.');
}
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => $mysqlDatabase ? 'mysql' : 'sqlite', 'DB_DATABASE' => $mysqlDatabase ?: ':memory:', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array'] as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
});

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function ensureSimplified(bool $condition, string $message): void
{
    if (! $condition) throw new RuntimeException($message);
}
function coreSnapshot(): array
{
    $snapshot = [];
    foreach (['users', 'roles', 'organizers', 'categories', 'reservations', 'tickets'] as $table) {
        $rows = DB::table($table)->get()->map(function ($row) {
            $data = (array) $row;
            ksort($data);
            return json_encode($data, JSON_THROW_ON_ERROR);
        })->all();
        sort($rows);
        $snapshot[$table] = $rows;
    }
    return $snapshot;
}

$paths = [];
foreach (glob(__DIR__.'/../database/migrations/*.php') as $file) {
    if (! str_starts_with(basename($file), '2026_11_15_10') && ! str_starts_with(basename($file), '2026_11_20_')) $paths[] = 'database/migrations/'.basename($file);
}
ensureSimplified(Artisan::call('migrate', ['--path' => $paths, '--force' => true]) === 0, 'Falha na preparação do schema anterior.');
ensureSimplified(Artisan::call('db:seed', ['--force' => true]) === 0, 'Falha no seeder.');
$participantId = DB::table('users')->where('email', 'alex@bilhete.test')->value('id');
$campaign = DB::table('campaigns')->orderBy('id')->first();
$photos = ['/art/bike.svg', '/art/camera.svg', '/art/sound.svg'];
DB::table('campaigns')->where('id', $campaign->id)->update(['featured' => true, 'photos' => json_encode($photos), 'status' => 'pausada']);
DB::table('password_reset_tokens')->insert(['email' => 'alex@bilhete.test', 'token' => 'legacy-token', 'created_at' => now()]);
$reservationId = DB::table('reservations')->insertGetId(['user_id' => $participantId, 'campaign_id' => $campaign->id,
    'expires' => now()->addMinutes(15), 'numbers' => '[1]', 'total' => $campaign->price, 'created_at' => now(), 'updated_at' => now()]);
DB::table('tickets')->where(['campaign_id' => $campaign->id, 'number' => 1])->update(['reservation_id' => $reservationId]);
DB::table('favorites')->insert(['user_id' => $participantId, 'campaign_id' => $campaign->id]);
DB::table('reviews')->insert(['user_id' => $participantId, 'campaign_id' => $campaign->id, 'score' => 5, 'comment' => 'Legado', 'created_at' => now(), 'updated_at' => now()]);
DB::table('reports')->insert(['user_id' => $participantId, 'campaign_id' => $campaign->id, 'message' => 'Legado', 'created_at' => now(), 'updated_at' => now()]);
DB::table('notices')->insert(['user_id' => $participantId, 'message' => 'Legado', 'created_at' => now(), 'updated_at' => now()]);
DB::table('activities')->insert(['user_id' => $participantId, 'action' => 'Legado', 'created_at' => now()]);
$removedResources = ['favorite.store', 'review.store', 'report.store', 'report.resolve', 'profile.update'];
foreach ($removedResources as $name) {
    $id = DB::table('resources')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('permissions')->insert(['role_id' => DB::table('users')->where('id', $participantId)->value('role_id'), 'resource_id' => $id]);
}
$before = coreSnapshot();
$campaignsBefore = DB::table('campaigns')->orderBy('id')->get()->map(function ($row) {
    $data = (array) $row;
    unset($data['featured']);
    if ($data['status'] === 'pausada') $data['status'] = 'ativa';
    $data['photos'] = json_encode(array_slice(json_decode($data['photos'], true), 0, 1));
    ksort($data);
    return $data;
})->all();
ensureSimplified(Artisan::call('migrate', ['--force' => true]) === 0, 'Migração de simplificação falhou.');
foreach (['favorites', 'reviews', 'reports', 'notices', 'activities'] as $table) {
    ensureSimplified(! Schema::hasTable($table), 'Tabela não removida: '.$table);
}
ensureSimplified(! Schema::hasColumn('campaigns', 'featured'), 'Coluna de destaque ainda existe.');
ensureSimplified(! Schema::hasTable('password_reset_tokens'), 'Tabela de recuperação ainda existe.');
ensureSimplified(! DB::table('campaigns')->where('status', 'pausada')->exists(), 'Campanha permaneceu presa no estado pausada.');
ensureSimplified(coreSnapshot() === $before, 'Dados essenciais alterados.');
$campaignsAfter = DB::table('campaigns')->orderBy('id')->get()->map(function ($row) {
    $data = (array) $row;
    $data['photos'] = json_encode(json_decode($data['photos'], true));
    ksort($data);
    return $data;
})->all();
ensureSimplified($campaignsBefore === $campaignsAfter, 'Dados de campanha alterados além de destaque e fotos excedentes.');
ensureSimplified(DB::table('resources')->whereIn('name', $removedResources)->count() === 0, 'Recursos antigos persistiram.');
ensureSimplified(DB::table('permissions')->count() === 41 && DB::table('resources')->count() === 23, 'Permissões principais alteradas.');
ensureSimplified(Artisan::call('migrate:rollback', ['--step' => 12, '--force' => true]) === 0, 'Rollback de simplificação falhou.');
foreach (['favorites', 'reviews', 'reports', 'notices', 'activities'] as $table) {
    ensureSimplified(Schema::hasTable($table) && DB::table($table)->count() === 0, 'Rollback não restaurou estrutura vazia: '.$table);
}
ensureSimplified(Schema::hasColumn('campaigns', 'featured'), 'Rollback não restaurou coluna de destaque.');
ensureSimplified(Schema::hasTable('password_reset_tokens') && DB::table('password_reset_tokens')->count() === 0, 'Rollback não restaurou estrutura vazia de tokens.');
ensureSimplified(coreSnapshot() === $before, 'Rollback alterou dados essenciais.');
ensureSimplified(Artisan::call('migrate', ['--force' => true]) === 0, 'Reaplicação falhou.');
ensureSimplified(coreSnapshot() === $before, 'Reaplicação alterou dados essenciais.');
echo "OK: atualização com dados legados, primeira foto preservada, remoção de módulos/permissões, dados principais intactos, rollback estrutural e reaplicação.\n";
