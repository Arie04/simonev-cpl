<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemetaan_cpl_pl', function (Blueprint $table) {
            $table->uuid('pemetaan_cpl_pl_id')->primary();
            // Pemetaan kurikulum: baris ikut terhapus dari kedua sisi (KD-20).
            $table->foreignUuid('cpl_id')->constrained('cpl', 'cpl_id')->cascadeOnDelete();
            $table->foreignUuid('profil_lulusan_id')->constrained('profil_lulusan', 'profil_lulusan_id')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cpl_id', 'profil_lulusan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemetaan_cpl_pl');
    }
};
