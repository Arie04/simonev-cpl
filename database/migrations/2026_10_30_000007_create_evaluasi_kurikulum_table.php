<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_kurikulum', function (Blueprint $table) {
            $table->uuid('evaluasi_kurikulum_id')->primary();
            $table->foreignUuid('kurikulum_id')->constrained('kurikulum', 'kurikulum_id')->restrictOnDelete();
            $table->string('tahap_evaluasi');
            $table->text('kinerja_mutu');
            $table->text('standar_kinerja_mutu');
            $table->text('kesenjangan')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_kurikulum');
    }
};
