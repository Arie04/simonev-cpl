<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemetaan_cpl_bk', function (Blueprint $table) {
            $table->uuid('pemetaan_cpl_bk_id')->primary();
            // Pemetaan kurikulum: baris ikut terhapus dari kedua sisi (KD-20).
            $table->foreignUuid('cpl_id')->constrained('cpl', 'cpl_id')->cascadeOnDelete();
            $table->foreignUuid('bahan_kajian_id')->constrained('bahan_kajian', 'bahan_kajian_id')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cpl_id', 'bahan_kajian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemetaan_cpl_bk');
    }
};
