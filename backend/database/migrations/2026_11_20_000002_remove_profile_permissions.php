<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->whereIn('resource_id', DB::table('resources')->select('id')->where('name', 'profile.update'))->delete();
    }

    public function down(): void
    {
        $resource = DB::table('resources')->where('name', 'profile.update')->value('id');
        if (! $resource) return;
        foreach (DB::table('roles')->whereIn('name', ['participante', 'organizador', 'administrador'])->pluck('id') as $role) {
            DB::table('permissions')->insertOrIgnore(['role_id' => $role, 'resource_id' => $resource]);
        }
    }
};
