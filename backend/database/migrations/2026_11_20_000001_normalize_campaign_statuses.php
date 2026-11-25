<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // These campaigns were already approved. Dates and reservation rules still apply.
        DB::table('campaigns')->where('status', 'pausada')->update(['status' => 'ativa']);
    }

    public function down(): void
    {
        // Do not pause unrelated active campaigns. Recover old statuses from backup if needed.
    }
};
