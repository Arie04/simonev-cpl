<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemetaan_bk_mk', function (Blueprint $table) {
            $table->uuid('pemetaan_bk_mk_id')->primary();
            // Pemetaan kurikulum: baris ikut terhapus dari kedua sisi (KD-20).
            $table->foreignUuid('bahan_kajian_id')->constrained('bahan_kajian', 'bahan_kajian_id')->cascadeOnDelete();
            $table->foreignUuid('mata_kuliah_id')->constrained('mata_kuliah', 'mata_kuliah_id')->cascadeOnDelete();
            $table->decimal('bobot_bk_mk', 5, 2);
            $table->timestamps();

            $table->unique(['bahan_kajian_id', 'mata_kuliah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemetaan_bk_mk');
    }
};
