<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peserta_kelas', function (Blueprint $table) {
            $table->uuid('peserta_kelas_id')->primary();
            // Ikut terhapus bersama kelasnya (selama belum punya nilai); mahasiswa yang
            // masih terdaftar di kelas tidak bisa dihapus (KD-20).
            $table->foreignUuid('kelas_kuliah_id')->constrained('kelas_kuliah', 'kelas_kuliah_id')->cascadeOnDelete();
            $table->foreignUuid('mahasiswa_id')->constrained('mahasiswa', 'mahasiswa_id')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['kelas_kuliah_id', 'mahasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peserta_kelas');
    }
};
