<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('campaign_id')->constrained();
            $t->unsignedTinyInteger('score');
            $t->text('comment');
            $t->timestamps();
            $t->unique(['user_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
