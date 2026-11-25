<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('reports');
    }

    public function down(): void
    {
        Schema::create('reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('campaign_id')->constrained();
            $t->text('message');
            $t->text('resolution')->nullable();
            $t->timestamps();
        });
    }
};
