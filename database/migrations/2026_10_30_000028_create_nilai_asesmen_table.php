<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_asesmen', function (Blueprint $table) {
            $table->uuid('nilai_asesmen_id')->primary();
            $table->foreignUuid('rencana_asesmen_id')->constrained('rencana_asesmen', 'rencana_asesmen_id')->restrictOnDelete();
            $table->foreignUuid('peserta_kelas_id')->constrained('peserta_kelas', 'peserta_kelas_id')->restrictOnDelete();
            // Wajib diisi: nilai yang belum diinput berarti barisnya belum ada (KD-29).
            $table->decimal('nilai', 6, 2);
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['rencana_asesmen_id', 'peserta_kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_asesmen');
    }
};
