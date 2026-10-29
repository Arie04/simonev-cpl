<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosen_kelas', function (Blueprint $table) {
            $table->uuid('dosen_kelas_id')->primary();
            // Ikut terhapus bersama kelasnya; dosen yang masih mengampu tidak bisa dihapus (KD-20).
            $table->foreignUuid('kelas_kuliah_id')->constrained('kelas_kuliah', 'kelas_kuliah_id')->cascadeOnDelete();
            $table->foreignUuid('dosen_id')->constrained('dosen', 'dosen_id')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['kelas_kuliah_id', 'dosen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosen_kelas');
    }
};
