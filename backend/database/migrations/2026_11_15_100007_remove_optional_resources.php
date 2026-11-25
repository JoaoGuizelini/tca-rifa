<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('resources')->whereIn('name', ['favorite.store', 'review.store', 'report.store', 'report.resolve'])->delete();
    }

    public function down(): void
    {
        foreach (['favorite.store', 'review.store', 'report.store', 'report.resolve'] as $name) {
            DB::table('resources')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
};
