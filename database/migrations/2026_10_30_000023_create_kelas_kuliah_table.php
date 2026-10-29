<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_kuliah', function (Blueprint $table) {
            $table->uuid('kelas_kuliah_id')->primary();
            $table->foreignUuid('mata_kuliah_id')->constrained('mata_kuliah', 'mata_kuliah_id')->restrictOnDelete();
            $table->foreignUuid('periode_akademik_id')->constrained('periode_akademik', 'periode_akademik_id')->restrictOnDelete();
            // Kombinasi MK, periode, dan nama kelas dicek unik di aplikasi (bagian 6 spesifikasi).
            $table->string('nama_kelas', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_kuliah');
    }
};
