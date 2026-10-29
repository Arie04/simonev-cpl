<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_akademik', function (Blueprint $table) {
            $table->uuid('periode_akademik_id')->primary();
            // Format "2026/2027". Kombinasi tahun dan semester dicek unik di aplikasi
            // karena ERD tidak memberi UQ (bagian 6 spesifikasi).
            $table->string('tahun_akademik', 9);
            $table->enum('semester_periode', ['Ganjil', 'Genap']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_akademik');
    }
};
