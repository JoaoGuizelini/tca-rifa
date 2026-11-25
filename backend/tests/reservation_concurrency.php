<?php

// Run after application_architecture.php in the same disposable MySQL schema.
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\User;

$database = getenv('BILHETE_TEST_MYSQL_DATABASE');
if (! $database || ! preg_match('/^bilhete_architecture_test_[a-f0-9]{16}$/', $database)) {
    throw new RuntimeException('Concorrência exige banco MySQL de teste isolado.');
}
$barrier = sys_get_temp_dir().'/bilhete-barrier-'.bin2hex(random_bytes(8));
$processes = [];
try {
    foreach (User::whereIn('email', ['alex@bilhete.test', 'pessoa@example.test'])->pluck('id') as $id) {
        $process = proc_open([PHP_BINARY, '-d', 'extension=pdo_mysql', __DIR__.'/support/reserve_worker.php', (string) $id, '2', '99', $barrier],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        check(is_resource($process), 'Falha ao iniciar worker.');
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    check(count($processes) === 2, 'Esperados dois participantes concorrentes.');
    foreach ($processes as [$process, $pipes]) {
        if (trim(fgets($pipes[1])) !== 'READY') {
            throw new RuntimeException('Worker não iniciou: '.stream_get_contents($pipes[2]));
        }
    }
    file_put_contents($barrier, 'go');
    $statuses = [];
    foreach ($processes as [$process, $pipes]) {
        $result = json_decode(stream_get_contents($pipes[1]), true);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        check(proc_close($process) === 0 && isset($result['status']), 'Worker falhou: '.$error);
        $statuses[] = $result['status'];
    }
    $processes = [];
    sort($statuses);
    check($statuses === [200, 409], 'Reservas concorrentes não retornaram sucesso e conflito.');
    check(Reservation::where('campaign_id', 2)->count() === 1, 'Mais de uma reserva persistida para o mesmo número.');
    check(Ticket::where('campaign_id', 2)->where('number', 99)->value('reservation_id') !== null, 'Número não vinculado à reserva vencedora.');
    echo "OK: dois processos concorrentes no MySQL; uma reserva aceita, outra rejeitada com conflito.\n";
} finally {
    if (is_file($barrier)) unlink($barrier);
    foreach ($processes as [$process, $pipes]) {
        if (is_resource($process)) proc_terminate($process);
        foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        if (is_resource($process)) proc_close($process);
    }
}
