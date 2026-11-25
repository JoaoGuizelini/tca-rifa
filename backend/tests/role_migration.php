<?php

// Verifies migration of existing users and rollback without touching MySQL.
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array'] as $key => $value) {
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

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function verify(bool $condition, string $message): void
{
    if (! $condition) throw new RuntimeException($message);
}
foreach (glob(__DIR__.'/../database/migrations/2026_01_01_*.php') as $file) {
    verify(Artisan::call('migrate', ['--path' => 'database/migrations/'.basename($file), '--force' => true]) === 0, 'Falha nas migrações originais.');
}
foreach (Role::NAMES as $index => $name) {
    DB::table('users')->insert(['name' => 'Pessoa '.$index, 'email' => "pessoa$index@example.test", 'cpf' => str_pad((string) $index, 11, '0', STR_PAD_LEFT),
        'phone' => '11912345678', 'password' => 'hash-preservado-'.$index, 'role' => $name, 'suspended' => $index === 1]);
}
$snapshot = fn () => DB::table('users')->orderBy('id')->get()->map(function ($user) {
    $data = (array) $user;
    ksort($data);
    return $data;
})->all();
$original = $snapshot();
$rolePaths = ['database/migrations/2026_11_13_000000_create_roles_table.php', 'database/migrations/2026_11_13_000001_link_users_to_roles.php'];
verify(Artisan::call('migrate', ['--path' => $rolePaths, '--force' => true]) === 0, 'Falha ao migrar os papéis.');
verify(Schema::hasColumn('users', 'role_id') && ! Schema::hasColumn('users', 'role'), 'Fonte duplicada de papel.');
foreach (User::orderBy('id')->get() as $index => $user) {
    verify($user->role instanceof Role && $user->role->name === $original[$index]['role'], 'Papel existente não preservado.');
    verify($user->password === $original[$index]['password'], 'Senha alterada pela migração.');
    verify($user->hasRole($original[$index]['role']), 'Verificação por Role falhou.');
    verify($user->toArray()['role'] === $original[$index]['role'], 'Contrato JSON alterado.');
}
verify(Artisan::call('migrate:rollback', ['--step' => 2, '--force' => true]) === 0, 'Rollback falhou.');
verify(! Schema::hasTable('roles') && ! Schema::hasColumn('users', 'role_id'), 'Rollback deixou estruturas novas.');
verify($snapshot() === $original, 'Rollback não restaurou os usuários.');
verify(Artisan::call('migrate', ['--path' => $rolePaths, '--force' => true]) === 0, 'Reaplicação falhou.');
Artisan::call('db:seed', ['--class' => Database\Seeders\RoleSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => Database\Seeders\RoleSeeder::class, '--force' => true]);
verify(Role::count() === 3, 'Seeder duplicou papéis.');
echo "OK: migração preserva usuários, senhas e papéis; rollback, reaplicação, relacionamento Role, JSON e seeder idempotente.\n";
