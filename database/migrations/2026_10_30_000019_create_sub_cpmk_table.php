<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_cpmk', function (Blueprint $table) {
            $table->uuid('sub_cpmk_id')->primary();
            // Sub-CPMK menempel ke pasangan CPMK–MK, bukan ke CPMK langsung (bagian 4 spesifikasi).
            $table->foreignUuid('pemetaan_cpmk_mk_id')->constrained('pemetaan_cpmk_mk', 'pemetaan_cpmk_mk_id')->restrictOnDelete();
            $table->string('kode_sub_cpmk', 20);
            $table->text('deskripsi_sub_cpmk');
            // Target bobot Sub-CPMK dalam MK, hanya untuk validasi (KD-08).
            $table->decimal('bobot_sub_cpmk', 5, 2);
            $table->text('materi')->nullable();
            $table->unsignedTinyInteger('pertemuan_mulai')->nullable();
            $table->unsignedTinyInteger('pertemuan_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['pemetaan_cpmk_mk_id', 'kode_sub_cpmk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_cpmk');
    }
};
