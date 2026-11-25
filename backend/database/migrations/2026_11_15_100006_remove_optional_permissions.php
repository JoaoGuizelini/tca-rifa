<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->whereIn('resource_id', DB::table('resources')->select('id')
            ->whereIn('name', ['favorite.store', 'review.store', 'report.store', 'report.resolve']))->delete();
    }

    public function down(): void
    {
        $roles = DB::table('roles')->pluck('id', 'name');
        $resources = DB::table('resources')->pluck('id', 'name');
        $grants = [
            'favorite.store' => ['participante', 'organizador', 'administrador'],
            'review.store' => ['participante', 'organizador'],
            'report.store' => ['participante', 'organizador', 'administrador'],
            'report.resolve' => ['administrador'],
        ];
        foreach ($grants as $resource => $names) {
            foreach ($names as $role) {
                if (isset($roles[$role], $resources[$resource])) {
                    DB::table('permissions')->insertOrIgnore(['role_id' => $roles[$role], 'resource_id' => $resources[$resource]]);
                }
            }
        }
    }
};
