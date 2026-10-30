<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rencana_asesmen_sub_cpmk', function (Blueprint $table) {
            $table->uuid('rencana_asesmen_sub_cpmk_id')->primary();
            // Ikut terhapus bersama asesmennya, tetapi Sub-CPMK yang sudah dipakai
            // tidak bisa dihapus agar bobot kelas lama tidak hilang (KD-08, KD-20).
            $table->foreignUuid('rencana_asesmen_id')->constrained('rencana_asesmen', 'rencana_asesmen_id')->cascadeOnDelete();
            $table->foreignUuid('sub_cpmk_id')->constrained('sub_cpmk', 'sub_cpmk_id')->restrictOnDelete();
            // Dasar perhitungan capaian: bagian asesmen yang mengukur Sub-CPMK ini, dalam % nilai akhir MK (5.1).
            $table->decimal('bobot_kontribusi', 5, 2);
            $table->timestamps();

            $table->unique(['rencana_asesmen_id', 'sub_cpmk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rencana_asesmen_sub_cpmk');
    }
};
