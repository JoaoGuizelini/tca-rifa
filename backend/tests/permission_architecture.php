<?php

// Can run alone (SQLite) or after the existing suite in the disposable MySQL DB.
require_once __DIR__.'/application_architecture.php';

use App\Models\Permission;
use App\Models\Resource;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

$service = app(PermissionService::class);
$participant = User::whereHas('role', fn ($q) => $q->where('name', Role::PARTICIPANT))->firstOrFail();
$admin = User::whereHas('role', fn ($q) => $q->where('name', Role::ADMINISTRATOR))->firstOrFail();
$organizer = User::where('email', 'aurora@bilhete.test')->firstOrFail();

function grant(User $user, string $name): void
{
    Permission::query()->insertOrIgnore(['role_id' => $user->role_id, 'resource_id' => Resource::where('name', $name)->firstOrFail()->id]);
}
function revoke(User $user, string $name): void
{
    Permission::where('role_id', $user->role_id)->where('resource_id', Resource::where('name', $name)->firstOrFail()->id)->delete();
}

foreach (Route::getRoutes() as $route) {
    $middleware = $route->gatherMiddleware();
    if (in_array('access', $middleware, true)) {
        check(in_array('permission', $middleware, true), 'Rota protegida sem middleware de permissão.');
        check($route->getName() && Resource::where('name', $route->getName())->exists(), 'Rota sem Resource: '.$route->uri());
    }
    foreach ($middleware as $item) check(! str_starts_with($item, 'access:'), 'Restrição antiga de papel contorna a matriz no banco.');
}
check(isset($service->getPermissions($participant->role_id)['campaign.index']), 'getPermissions não retorna mapa de recursos.');
check($service->loadPermissions($participant->role_id) === $service->getPermissions($participant->role_id), 'loadPermissions divergente.');
check(! $service->isAuthorized('inexistente', $admin) && ! $service->isAuthorized('admin.index', null), 'Acesso sem recurso ou usuário permitido.');
$permission = Permission::with(['role', 'resource'])->firstOrFail();
check($permission->role instanceof Role && $permission->resource instanceof Resource, 'Relacionamentos Permission incompletos.');
check($admin->role->resource()->where('name', 'admin.index')->exists(), 'Role.resource não consulta permissions.');

revoke($participant, 'campaign.index');
app('session')->put('user_permissions', ['campaign.index' => true]);
check(! $service->isAuthorized('campaign.index', $participant), 'Cache antigo concedeu permissão revogada.');
http('GET', '/api/campanhas', $participant, expected: 403);
grant($participant, 'campaign.index');
http('GET', '/api/campanhas', $participant);

revoke($admin, 'category.store');
http('POST', '/api/admin/categorias', $admin, ['name' => 'Não deve criar'], 403);
check(! app(App\Policies\CategoryPolicy::class)->create($admin), 'Policy administrativa ignora revogação.');
grant($admin, 'category.store');
grant($participant, 'category.store');
http('POST', '/api/admin/categorias', $participant, ['name' => 'Permissão concedida'], 204);
$categoryId = App\Models\Category::where('name', 'Permissão concedida')->firstOrFail()->id;
grant($participant, 'category.update');
http('POST', '/api/admin/categorias/'.$categoryId, $participant, ['name' => 'Permissão editada'], 204);
revoke($participant, 'category.update');
http('POST', '/api/admin/categorias/'.$categoryId, $participant, ['name' => 'Não deve editar'], 403);
revoke($participant, 'category.store');
http('POST', '/api/admin/categorias', $participant, ['name' => 'Não deve criar'], 403);
http('DELETE', '/api/admin/categorias/'.$categoryId, $admin, expected: 204);

// Existing in-memory user/role snapshots must not survive a database change.
$originalRole = $participant->role_id;
DB::table('users')->where('id', $participant->id)->update(['role_id' => $admin->role_id]);
check($service->isAuthorized('admin.index', $participant), 'Mudança de role_id não refletida no serviço existente.');
DB::table('users')->where('id', $participant->id)->update(['role_id' => $originalRole, 'suspended' => true]);
check(! $service->isAuthorized('campaign.index', $participant), 'Suspensão no banco ignorada.');
DB::table('users')->where('id', $participant->id)->update(['suspended' => false]);
check(! $service->isAuthorized('admin.index', $participant), 'Papel anterior permaneceu autorizado.');

$draft = ['title' => 'Rascunho privado', 'description' => 'Teste de acesso', 'prize' => 'Livro', 'rules' => 'Regras', 'quantity' => 10,
    'price' => 100, 'starts' => today()->format('Y-m-d'), 'ends' => today()->addDays(2)->format('Y-m-d'), 'category_id' => 1];
$draftId = http('POST', '/api/estudio/campanhas', $organizer, $draft)['id'];
http('GET', '/api/campanhas/'.$draftId, $participant, expected: 404);
grant($participant, 'campaign.viewPrivate');
http('GET', '/api/campanhas/'.$draftId, $participant);
grant($participant, 'campaign.update');
grant($participant, 'campaign.change');
http('POST', '/api/estudio/campanhas/'.$draftId, $participant, $draft, 404);
http('POST', '/api/estudio/campanhas/'.$draftId.'/status', $participant, ['action' => 'enviar'], 404);
revoke($participant, 'campaign.viewPrivate');
revoke($participant, 'campaign.update');
revoke($participant, 'campaign.change');
http('GET', '/api/campanhas/'.$draftId, $participant, expected: 404);

// Permission to create a reservation must not be needed to confirm an existing one.
$booking = App\Models\Reservation::where('user_id', $participant->id)->where('status', 'confirmada')->firstOrFail();
revoke($participant, 'reservation.store');
check(app(App\Policies\ReservationPolicy::class)->confirm($participant, $booking), 'Confirmar depende indevidamente da permissão de criar.');
check(! app(App\Policies\ReservationPolicy::class)->confirm($organizer, $booking), 'Permissão removeu a restrição de propriedade.');
grant($participant, 'reservation.store');
revoke($admin, 'organizer.decide');
check(app(App\Policies\OrganizerPolicy::class)->document($admin) && ! app(App\Policies\OrganizerPolicy::class)->decide($admin), 'Download e decisão compartilham permissão indevida.');
grant($admin, 'organizer.decide');

Route::get('/api/test-no-name', fn () => ['ok' => true])->middleware(['web', 'access', 'permission']);
Route::get('/api/test-no-grant', fn () => ['ok' => true])->middleware(['web', 'access', 'permission'])->name('no.grant');
http('GET', '/api/test-no-name', $admin, expected: 403);
http('GET', '/api/test-no-grant', $admin, expected: 403);

$count = Permission::count();
$resources = Resource::count();
Artisan::call('db:seed', ['--class' => Database\Seeders\PermissionSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => Database\Seeders\PermissionSeeder::class, '--force' => true]);
check(Permission::count() === $count && Resource::count() === $resources, 'Seeders duplicaram permissões ou recursos.');
try {
    Permission::query()->insert(['role_id' => $admin->role_id, 'resource_id' => Resource::where('name', 'admin.index')->firstOrFail()->id]);
    throw new RuntimeException('Permissão duplicada aceita.');
} catch (Illuminate\Database\QueryException $expected) {}
try {
    Permission::query()->insert(['role_id' => 999999, 'resource_id' => Resource::firstOrFail()->id]);
    throw new RuntimeException('Chave estrangeira inválida aceita.');
} catch (Illuminate\Database\QueryException $expected) {}

foreach (['favorite.store', 'review.store', 'report.store', 'report.resolve', 'profile.update'] as $removed) {
    check(! Resource::where('name', $removed)->exists(), 'Seeder recriou recurso removido: '.$removed);
    check(! $service->isAuthorized($removed, $admin), 'Permissão removida continua autorizada: '.$removed);
}
http('GET', '/api/admin', $admin);
echo "OK: Resource/Permission/PermissionService, cobertura de rotas, concessão/revogação imediata, troca de papel, suspensão, propriedade, recursos desconhecidos, seeders, integridade e permissões removidas.\n";
