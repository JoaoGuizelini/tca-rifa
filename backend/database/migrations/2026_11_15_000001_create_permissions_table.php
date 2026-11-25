<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('resource_id')->constrained('resources');
            $table->primary(['role_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
