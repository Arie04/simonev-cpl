<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_lulusan_profesi', function (Blueprint $table) {
            $table->uuid('profil_lulusan_profesi_id')->primary();
            // Pemetaan kurikulum: baris ikut terhapus dari kedua sisi (KD-20).
            $table->foreignUuid('profil_lulusan_id')->constrained('profil_lulusan', 'profil_lulusan_id')->cascadeOnDelete();
            $table->foreignUuid('profesi_id')->constrained('profesi', 'profesi_id')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['profil_lulusan_id', 'profesi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_lulusan_profesi');
    }
};
