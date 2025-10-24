<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Aumenta el tamaño de la PK 'codigo' a 64 (UTF8MB4 → 256 bytes, seguro).
        DB::statement("ALTER TABLE `data` MODIFY `codigo` VARCHAR(64) NOT NULL");
        // La PK se conserva automáticamente.
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `data` MODIFY `codigo` VARCHAR(20) NOT NULL");
    }
};
