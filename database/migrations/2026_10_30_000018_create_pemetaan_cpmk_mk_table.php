<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemetaan_cpmk_mk', function (Blueprint $table) {
            $table->uuid('pemetaan_cpmk_mk_id')->primary();
            // Cascade dari kedua sisi, tetapi tertahan oleh sub_cpmk (restrict)
            // jika pemetaan ini sudah punya Sub-CPMK (KD-20).
            $table->foreignUuid('cpmk_id')->constrained('cpmk', 'cpmk_id')->cascadeOnDelete();
            $table->foreignUuid('mata_kuliah_id')->constrained('mata_kuliah', 'mata_kuliah_id')->cascadeOnDelete();
            // Target bobot CPMK dalam MK ("skor maks CPMK", Tabel 19 APTIKOM), hanya untuk validasi (KD-08).
            $table->decimal('bobot_cpmk', 5, 2);
            $table->timestamps();

            $table->unique(['cpmk_id', 'mata_kuliah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemetaan_cpmk_mk');
    }
};
