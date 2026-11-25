<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campaign_id')->constrained();
            $t->unsignedInteger('number');
            $t->foreignId('reservation_id')->nullable()->constrained();
            $t->unique(['campaign_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
