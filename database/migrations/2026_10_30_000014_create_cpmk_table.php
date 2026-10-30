<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpmk', function (Blueprint $table) {
            $table->uuid('cpmk_id')->primary();
            $table->foreignUuid('cpl_id')->constrained('cpl', 'cpl_id')->restrictOnDelete();
            $table->string('kode_cpmk', 20);
            $table->text('deskripsi_cpmk');
            $table->timestamps();

            // Keunikan kode CPMK dalam satu kurikulum dicek di aplikasi (bagian 4 spesifikasi).
            $table->unique(['cpl_id', 'kode_cpmk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpmk');
    }
};
