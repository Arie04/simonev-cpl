<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_lulusan', function (Blueprint $table) {
            $table->uuid('profil_lulusan_id')->primary();
            $table->foreignUuid('kurikulum_id')->constrained('kurikulum', 'kurikulum_id')->restrictOnDelete();
            $table->string('kode_pl', 20);
            $table->text('deskripsi_pl');
            $table->string('unsur')->nullable();
            $table->string('sumber')->nullable();
            $table->timestamps();

            // Kode unik per kurikulum, bukan unik global (KD-07).
            $table->unique(['kurikulum_id', 'kode_pl']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_lulusan');
    }
};
