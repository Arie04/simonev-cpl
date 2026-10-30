<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rencana_asesmen', function (Blueprint $table) {
            $table->uuid('rencana_asesmen_id')->primary();
            $table->foreignUuid('kelas_kuliah_id')->constrained('kelas_kuliah', 'kelas_kuliah_id')->restrictOnDelete();
            $table->foreignUuid('metode_penilaian_id')->constrained('metode_penilaian', 'metode_penilaian_id')->restrictOnDelete();
            $table->string('nama_asesmen');
            // Bobot asesmen terhadap nilai akhir MK, harus sama dengan Σ bobot_kontribusi asesmen ini (5.1).
            $table->decimal('bobot_asesmen', 5, 2);
            $table->decimal('nilai_maksimum', 6, 2);
            $table->date('tanggal_asesmen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rencana_asesmen');
    }
};
