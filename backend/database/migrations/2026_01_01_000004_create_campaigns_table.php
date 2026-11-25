<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('users');
            $t->foreignId('category_id')->constrained();
            $t->string('title');
            $t->text('description');
            $t->string('prize');
            $t->text('rules');
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('price');
            $t->date('starts');
            $t->date('ends');
            $t->string('status')->default('rascunho');
            $t->boolean('featured')->default(false);
            $t->json('photos');
            $t->text('reason')->nullable();
            $t->unsignedInteger('winner')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
