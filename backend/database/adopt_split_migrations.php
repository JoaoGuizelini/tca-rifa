<?php

// Run only on databases that already applied the former combined migration.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$old = '2026_01_01_000000_create_marketplace';
$files = glob(__DIR__.'/migrations/2026_01_01_*_create_*_table.php');
sort($files);
if (count($files) !== 12) {
    throw new RuntimeException('Esperadas as 12 migrações individuais.');
}
if (! Schema::hasTable('migrations')) {
    echo "Banco novo: execute php artisan migrate normalmente.\n";
    exit(0);
}

DB::transaction(function () use ($old, $files) {
    $previous = DB::table('migrations')->where('migration', $old)->lockForUpdate()->first();
    if (! $previous) {
        echo "Migração antiga ausente; nenhuma alteração realizada.\n";
        return;
    }

    $rows = [];
    foreach ($files as $file) {
        $name = basename($file, '.php');
        preg_match('/_create_(.+)_table$/', $name, $match);
        if (! Schema::hasTable($match[1])) {
            throw new RuntimeException('Tabela ausente: '.$match[1]);
        }
        if (DB::table('migrations')->where('migration', $name)->exists()) {
            throw new RuntimeException('Histórico misto; verifique a migração '.$name);
        }
        $rows[] = ['migration' => $name, 'batch' => $previous->batch];
    }

    DB::table('migrations')->insert($rows);
    DB::table('migrations')->where('id', $previous->id)->delete();
    echo "Histórico atualizado para 12 migrações. Tabelas e dados preservados.\n";
});
