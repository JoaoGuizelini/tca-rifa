<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizers', function (Blueprint $t) {
            $t->foreignId('user_id')->primary()->constrained('users');
            $t->string('public_name');
            $t->text('bio');
            $t->string('document');
            $t->string('status')->default('pendente');
            $t->text('reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};
