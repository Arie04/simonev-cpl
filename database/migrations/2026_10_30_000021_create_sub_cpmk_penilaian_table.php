<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_cpmk_penilaian', function (Blueprint $table) {
            $table->uuid('sub_cpmk_penilaian_id')->primary();
            // Ikut terhapus bersama Sub-CPMK-nya; metode yang masih dipakai tidak bisa dihapus (KD-20).
            $table->foreignUuid('sub_cpmk_id')->constrained('sub_cpmk', 'sub_cpmk_id')->cascadeOnDelete();
            $table->foreignUuid('metode_penilaian_id')->constrained('metode_penilaian', 'metode_penilaian_id')->restrictOnDelete();
            $table->string('teknik_penilaian')->nullable();
            $table->string('instrumen')->nullable();
            $table->text('kriteria')->nullable();
            $table->timestamps();

            $table->unique(['sub_cpmk_id', 'metode_penilaian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_cpmk_penilaian');
    }
};
