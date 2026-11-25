<?php

// Runs category checks too and shares their isolated in-memory test database.
require __DIR__.'/category_architecture.php';

use App\Models\Campaign;
use App\Models\Organizer;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$password = 'SenhaLocalTeste123';
foreach ([$admin, $participant, $organizer] as $user) {
    $user->forceFill(['password' => $password])->save();
}
http('POST', '/api/login', data: ['email' => $participant->email, 'password' => 'errada'], expected: 422);
$login = http('POST', '/api/login', data: ['email' => strtoupper($participant->email), 'password' => $password]);
check($login['role'] === 'participante' && ! isset($login['password']), 'Login ou proteção de senha falhou.');
http('POST', '/api/logout', $participant, expected: 204);

$registration = ['name' => 'Pessoa Teste', 'email' => 'pessoa@example.test', 'cpf' => '935.411.347-80', 'phone' => '(11) 91234-5678',
    'password' => $password, 'password_confirmation' => $password, 'role' => 'administrador',
    'role_id' => \App\Models\Role::where('name', \App\Models\Role::ADMINISTRATOR)->firstOrFail()->id, 'suspended' => true];
http('POST', '/api/cadastro', data: array_replace($registration, ['cpf' => '11111111111']), expected: 422);
$registered = http('POST', '/api/cadastro', data: $registration);
check($registered['role'] === 'participante' && ! $registered['suspended'], 'Cadastro aceitou privilégios do cliente ou omitiu os padrões.');
check($registered['role_id'] === \App\Models\Role::where('name', \App\Models\Role::PARTICIPANT)->firstOrFail()->id, 'Cadastro aceitou role_id do cliente.');
$newUser = User::findOrFail($registered['id']);
$beforeProfile = $newUser->toArray();
http('POST', '/api/perfil', $newUser, ['name' => 'Não deve alterar'], 404);
check($newUser->fresh()->toArray() === $beforeProfile, 'Dados cadastrais foram alterados.');
http('GET', '/api/campanhas', expected: 401);
$catalog = http('GET', '/api/campanhas', $participant);
check(count($catalog) === 3 && is_string($catalog[0]['photos']), 'Contrato do catálogo alterado.');
check(count(http('GET', '/api/campanhas?q=Bicicleta&category=1&sort=popular', $participant)) === 1, 'Filtros do catálogo falharam.');
$detail = http('GET', '/api/campanhas/1', $participant);
check(count($detail['tickets']) === 100 && $detail['open'], 'Detalhes ou disponibilidade incorretos.');
check(array_keys($detail) === ['campaign', 'tickets', 'open'], 'Resposta da campanha contém funcionalidades removidas.');
check(! array_key_exists('featured', $detail['campaign']), 'Destaque continua exposto na campanha.');
check(array_keys(http('GET', '/api/area', $participant)) === ['reservations'], 'Minha área contém favoritos ou notificações.');
foreach (['favorito' => ['active' => true], 'avaliar' => ['score' => 5, 'comment' => 'Teste'], 'denunciar' => ['message' => 'Mensagem de teste']] as $action => $data) {
    http('POST', '/api/campanhas/1/'.$action, $participant, $data, 404);
}
http('POST', '/api/admin/denuncias/1', $admin, ['resolution' => 'Recurso removido'], 404);
foreach (['favorites', 'reviews', 'reports', 'notices', 'activities'] as $table) {
    check(! Illuminate\Support\Facades\Schema::hasTable($table), 'Tabela opcional ainda presente: '.$table);
}
http('POST', '/api/campanhas/1/reservar', $admin, ['numbers' => [1]], 403);
http('POST', '/api/campanhas/1/reservar', $organizer, ['numbers' => [1]], 409);
http('POST', '/api/campanhas/1/reservar', $participant, ['numbers' => [1, 1]], 422);
http('POST', '/api/campanhas/1/reservar', $participant, ['numbers' => range(1, 51)], 422);
$booking = http('POST', '/api/campanhas/1/reservar', $participant, ['numbers' => [1, 2]])['id'];
$reservationCount = Reservation::count();
http('POST', '/api/campanhas/1/reservar', $newUser, ['numbers' => [2, 4]], 409);
check(Reservation::count() === $reservationCount && Ticket::where('campaign_id', 1)->where('number', 4)->value('reservation_id') === null,
    'Seleção com número indisponível deixou uma reserva parcial.');
http('POST', '/api/reservas/'.$booking.'/confirmar', $newUser, expected: 404);
http('POST', '/api/reservas/'.$booking.'/confirmar', $participant, expected: 204);
http('POST', '/api/reservas/'.$booking.'/confirmar', $participant, expected: 409);
check(array_keys(http('GET', '/api/organizadores/'.$organizer->id, $participant)) === ['organizer', 'campaigns'], 'Perfil de organizador contém avaliações.');
$expired = http('POST', '/api/campanhas/1/reservar', $newUser, ['numbers' => [3]])['id'];
Reservation::whereKey($expired)->update(['expires' => now()->subMinute()]);
app(ReservationService::class)->sweep();
check(Reservation::findOrFail($expired)->status === 'expirada' && Ticket::where('campaign_id', 1)->where('number', 3)->value('reservation_id') === null, 'Expiração não liberou número.');
http('POST', '/api/reservas/'.$expired.'/confirmar', $newUser, expected: 409);
http('POST', '/api/campanhas/1/reservar', $participant, ['numbers' => [3]]);

$draft = ['title' => 'Campanha de teste', 'description' => 'Descrição de teste', 'prize' => 'Livro', 'rules' => 'Regulamento de teste',
    'quantity' => 10, 'price' => 250, 'starts' => today()->format('Y-m-d'), 'ends' => today()->addDays(2)->format('Y-m-d'),
    'category_id' => 1, 'owner_id' => $participant->id, 'status' => 'ativa'];
http('POST', '/api/estudio/campanhas', $participant, $draft, 403);
$campaignId = http('POST', '/api/estudio/campanhas', $organizer, $draft)['id'];
check(Campaign::findOrFail($campaignId)->owner_id === $organizer->id && Campaign::findOrFail($campaignId)->status === 'rascunho', 'Rascunho aceitou atributos protegidos.');
http('GET', '/api/campanhas/'.$campaignId, $participant, expected: 404);
http('GET', '/api/campanhas/'.$campaignId, $organizer);
http('GET', '/api/campanhas/'.$campaignId, $admin);
http('POST', '/api/estudio/campanhas/'.$campaignId, $organizer, array_replace($draft, ['title' => 'Rascunho editado']));
http('POST', '/api/estudio/campanhas/'.$campaignId.'/status', $organizer, ['action' => 'enviar']);
http('POST', '/api/admin/campanhas/'.$campaignId, $admin, ['approve' => false, 'reason' => 'Ajustar texto']);
http('POST', '/api/estudio/campanhas/'.$campaignId, $organizer, $draft);
http('POST', '/api/estudio/campanhas/'.$campaignId.'/status', $organizer, ['action' => 'enviar']);
http('POST', '/api/admin/campanhas/'.$campaignId, $admin, ['approve' => true, 'reason' => 'Aprovada']);
check(Ticket::where('campaign_id', $campaignId)->count() === 10, 'Quantidade de números gerados incorreta.');
http('POST', '/api/admin/campanhas/'.$campaignId, $admin, ['approve' => true, 'reason' => 'Repetida'], 409);
http('POST', '/api/estudio/campanhas/'.$campaignId, $organizer, $draft, 409);
http('POST', '/api/estudio/campanhas/'.$campaignId.'/status', $organizer, ['action' => 'pausar'], 422);
check(Campaign::findOrFail($campaignId)->status === 'ativa', 'Ação removida alterou a campanha.');
http('POST', '/api/estudio/campanhas/'.$campaignId.'/status', $organizer, ['action' => 'retomar'], 422);
http('POST', '/api/estudio/campanhas/'.$campaignId.'/status', $organizer, ['action' => 'cancelar']);
http('POST', '/api/estudio/campanhas/1/status', $organizer, ['action' => 'cancelar'], 409);
http('POST', '/api/estudio/campanhas/1/status', $organizer, ['action' => 'resultado', 'winner' => 1], 409);
Campaign::whereKey(1)->update(['ends' => today()->subDay()->format('Y-m-d')]);
http('POST', '/api/estudio/campanhas/1/status', $organizer, ['action' => 'resultado', 'winner' => 100], 422);
http('POST', '/api/estudio/campanhas/1/status', $organizer, ['action' => 'resultado', 'winner' => 1]);
check(Campaign::findOrFail(1)->winner === 1, 'Resultado não publicado.');

// Uploads use a separate temporary storage disk and real file MIME detection.
$storageRoot = sys_get_temp_dir().'/bilhete-test-'.bin2hex(random_bytes(8));
mkdir($storageRoot, 0700);
config(['filesystems.disks.local.root' => $storageRoot]);
Storage::forgetDisk('local');
$documentFile = tempnam(sys_get_temp_dir(), 'bilhete-pdf-');
$photoFile = tempnam(sys_get_temp_dir(), 'bilhete-png-');
file_put_contents($documentFile, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
file_put_contents($photoFile, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
try {
    $document = fn () => new UploadedFile($documentFile, 'identidade.pdf', 'application/pdf', null, true);
    $application = ['public_name' => 'Organizador Teste', 'bio' => 'Apresentação'];
    http('POST', '/api/verificacao', $newUser, $application, 204, ['document' => $document()]);
    http('POST', '/api/verificacao', $newUser, $application, 409, ['document' => $document()]);
    http('GET', '/api/admin/documentos/'.$newUser->id, $participant, expected: 403);
    $download = http('GET', '/api/admin/documentos/'.$newUser->id, $admin, raw: true);
    check(str_contains($download->headers->get('Content-Disposition'), 'attachment'), 'Documento não servido como download.');
    http('POST', '/api/admin/organizadores/'.$newUser->id, $admin, ['approve' => false, 'reason' => 'Enviar novamente']);
    http('POST', '/api/verificacao', $newUser, $application, 204, ['document' => $document()]);
    http('POST', '/api/admin/organizadores/'.$newUser->id, $admin, ['approve' => true, 'reason' => 'Documento conferido']);
    $newUser = $newUser->fresh();
    check($newUser->hasRole(\App\Models\Role::ORGANIZER) && Organizer::findOrFail($newUser->id)->status === 'aprovado', 'Aprovação de organizador falhou.');
    $photo = new UploadedFile($photoFile, 'foto.png', 'image/png', null, true);
    $extraPhoto = new UploadedFile($photoFile, 'outra-foto.png', 'image/png', null, true);
    $beforePhotoValidation = Campaign::count();
    http('POST', '/api/estudio/campanhas', $newUser, $draft, 422, ['photos' => [$photo, $extraPhoto]]);
    check(Campaign::count() === $beforePhotoValidation, 'Envio de duas fotos criou campanha indevidamente.');
    $withPhoto = http('POST', '/api/estudio/campanhas', $newUser, $draft, files: ['photos' => [$photo]])['id'];
    check(count(Campaign::findOrFail($withPhoto)->photos) === 1, 'Campanha não contém exatamente uma foto.');
    $path = Campaign::findOrFail($withPhoto)->photos[0];
    http('GET', $path, $newUser, raw: true);
    http('GET', $path, $participant, expected: 404);
    http('GET', $path, $admin, raw: true);
    http('POST', '/api/estudio/campanhas/'.$withPhoto, $organizer, $draft, 404);
    http('POST', '/api/estudio/campanhas/'.$withPhoto.'/status', $organizer, ['action' => 'enviar'], 404);
    http('POST', '/api/estudio/campanhas/'.$withPhoto, $newUser, $draft);
    check(Campaign::findOrFail($withPhoto)->photos === [$path], 'Edição sem foto não preservou a imagem única.');
    http('POST', '/api/estudio/campanhas/'.$withPhoto, $newUser, $draft, 422, ['photos' => [$photo, $extraPhoto]]);
    check(Campaign::findOrFail($withPhoto)->photos === [$path], 'Edição inválida com duas fotos alterou a imagem existente.');
    http('POST', '/api/estudio/campanhas/'.$withPhoto.'/status', $newUser, ['action' => 'enviar']);
    http('POST', '/api/admin/campanhas/'.$withPhoto, $admin, ['approve' => true, 'reason' => 'Aprovada']);
    http('GET', $path, $participant, raw: true);
    echo "OK: documentos privados, análise de identidade, foto única, rejeição de múltiplas fotos e visibilidade por propriedade/status.\n";
} finally {
    // Only delete files beneath the uniquely created test directory.
    $resolvedRoot = realpath($storageRoot);
    check($resolvedRoot !== false && str_starts_with(basename($resolvedRoot), 'bilhete-test-'), 'Diretório de limpeza inválido.');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolvedRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) {
        check(str_starts_with($entry->getRealPath(), $resolvedRoot.DIRECTORY_SEPARATOR), 'Arquivo fora do diretório de teste.');
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($resolvedRoot);
    unlink($documentFile);
    unlink($photoFile);
}

http('GET', '/api/admin', $participant, expected: 403);
$dashboard = http('GET', '/api/admin', $admin);
check(array_keys($dashboard) === ['users', 'organizers', 'campaigns'], 'Painel administrativo contém denúncias ou atividades.');
http('POST', '/api/admin/usuarios/'.$admin->id, $admin, ['suspended' => true], 403);
http('POST', '/api/admin/usuarios/'.$participant->id, $admin, ['suspended' => true], 204);
http('GET', '/api/area', $participant->fresh(), expected: 403);
http('POST', '/api/login', data: ['email' => $participant->email, 'password' => $password], expected: 422);
http('POST', '/api/admin/usuarios/'.$participant->id, $admin, ['suspended' => false], 204);
http('POST', '/api/admin/usuarios/'.$organizer->id, $admin, ['suspended' => true], 204);
check(count(http('GET', '/api/campanhas', $participant->fresh())) === 1, 'Catálogo mostrou campanhas de organizador suspenso.');
http('GET', '/api/organizadores/'.$organizer->id, $participant, expected: 404);
http('POST', '/api/admin/usuarios/'.$organizer->id, $admin, ['suspended' => false], 204);

http('POST', '/api/recuperar', data: ['email' => $participant->email], expected: 404);
http('POST', '/api/redefinir', data: ['email' => $participant->email], expected: 404);
check(! Illuminate\Support\Facades\Schema::hasTable('password_reset_tokens'), 'Recuperação ainda possui tabela ativa.');
echo "OK: fluxos principais preservados; perfil imutável, recuperação e ações de pausa/retomada indisponíveis.\n";
