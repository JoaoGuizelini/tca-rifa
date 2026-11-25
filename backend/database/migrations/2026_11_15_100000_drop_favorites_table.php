<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('favorites');
    }

    public function down(): void
    {
        Schema::create('favorites', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained();
            $t->foreignId('campaign_id')->constrained();
            $t->primary(['user_id', 'campaign_id']);
        });
    }
};
