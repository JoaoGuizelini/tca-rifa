<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('resources')->where('name', 'profile.update')->delete();
    }

    public function down(): void
    {
        DB::table('resources')->insertOrIgnore(['name' => 'profile.update', 'created_at' => now(), 'updated_at' => now()]);
    }
};
