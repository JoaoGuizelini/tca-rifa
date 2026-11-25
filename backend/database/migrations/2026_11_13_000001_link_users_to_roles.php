<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->pluck('id', 'name');
        $unknown = DB::table('users')->whereNull('role')->orWhereNotIn('role', $roles->keys()->all())->exists();
        if ($unknown || ! isset($roles['participante'])) {
            throw new RuntimeException('Há papéis sem correspondência. Nenhum papel de usuário foi alterado.');
        }

        Schema::table('users', function (Blueprint $table) use ($roles) {
            $table->foreignId('role_id')->default($roles['participante'])->constrained('roles');
        });
        foreach ($roles as $name => $id) {
            DB::table('users')->where('role', $name)->update(['role_id' => $id]);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('participante'));
        foreach (DB::table('roles')->pluck('name', 'id') as $id => $name) {
            DB::table('users')->where('role_id', $id)->update(['role' => $name]);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
