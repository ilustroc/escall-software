<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tipificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipificacion', 60)->unique();
            $table->integer('puntos')->default(999);   // menor = mejor
            $table->string('mc', 10)->nullable();      // CD+, NC-, etc.
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('tipificaciones');
    }
};
