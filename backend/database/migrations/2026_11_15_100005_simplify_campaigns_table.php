<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('campaigns')->orderBy('id')->chunkById(200, function ($campaigns) {
            foreach ($campaigns as $campaign) {
                $photos = json_decode($campaign->photos, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($photos)) throw new RuntimeException('Fotos inválidas na campanha '.$campaign->id);
                if (count($photos) > 1) {
                    DB::table('campaigns')->where('id', $campaign->id)->update(['photos' => json_encode(array_slice($photos, 0, 1), JSON_THROW_ON_ERROR)]);
                }
            }
        });
        Schema::table('campaigns', fn (Blueprint $table) => $table->dropColumn('featured'));
    }

    public function down(): void
    {
        Schema::table('campaigns', fn (Blueprint $table) => $table->boolean('featured')->default(false));
        // Previous photo galleries and highlight flags can only be recovered from backup.
    }
};
