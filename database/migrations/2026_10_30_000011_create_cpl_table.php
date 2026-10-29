<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpl', function (Blueprint $table) {
            $table->uuid('cpl_id')->primary();
            $table->foreignUuid('kurikulum_id')->constrained('kurikulum', 'kurikulum_id')->restrictOnDelete();
            $table->string('kode_cpl', 20);
            $table->string('kategori_cpl', 50);
            $table->text('deskripsi_cpl');
            $table->timestamps();

            // Kode unik per kurikulum, bukan unik global (KD-07).
            $table->unique(['kurikulum_id', 'kode_cpl']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpl');
    }
};
