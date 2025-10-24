<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data', function (Blueprint $table) {
            // Clave primaria (string)
            $table->string('codigo', 20)->primary();

            // Campos de texto
            $table->string('dni', 20)->index();
            $table->string('titular', 150);
            $table->string('cartera', 100)->index();
            $table->string('entidad', 100)->index();
            $table->string('cosecha', 100)->nullable();
            $table->string('sub_cartera', 100)->nullable();
            $table->string('producto', 100)->nullable();
            $table->string('sub_producto', 100)->nullable();
            $table->string('historico', 120)->nullable();
            $table->string('departamento', 100)->nullable();

            // Números/monedas
            $table->decimal('deuda_total',   14, 2)->nullable();   // ej. 20853.32
            $table->decimal('deuda_capital', 14, 2)->nullable();   // ej. 14350.00
            $table->decimal('campania',      14, 2)->nullable();   // ej. 10426.66

            // Porcentaje: en tus datos aparece hasta 9 decimales (p.ej. 0.726596516)
            $table->decimal('porcentaje', 12, 9)->nullable();

            $table->timestamps(); // created_at / updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data');
    }
};
