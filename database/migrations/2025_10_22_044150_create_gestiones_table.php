<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gestiones', function (Blueprint $table) {
            $table->id();

            // Campos solicitados
            $table->date('fecha_gestion');                           // DATE
            $table->string('dni', 20);                       // STRING
            $table->string('telefono', 25)->nullable();      // STRING
            $table->string('status', 100)->nullable();       // STRING
            $table->string('tipificacion', 120)->nullable(); // STRING
            $table->string('observacion', 500)->nullable();  // STRING (ampliado a 500)
            $table->date('fecha_pago')->nullable();                  // DATE
            $table->integer('monto_pago')->nullable();               // INT
            $table->string('nombre', 150)->nullable();       // STRING

            $table->timestamps();

            // Índices útiles
            $table->index(['dni', 'fecha_gestion']);
            $table->index('status');
            $table->index('tipificacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestiones');
    }
};
