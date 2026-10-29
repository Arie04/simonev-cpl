<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_kajian', function (Blueprint $table) {
            $table->uuid('bahan_kajian_id')->primary();
            $table->foreignUuid('kurikulum_id')->constrained('kurikulum', 'kurikulum_id')->restrictOnDelete();
            $table->string('kode_bk', 20);
            $table->string('nama_bahan_kajian');
            $table->string('kategori_bk', 50)->nullable();
            // Kolom dokumentasi Tabel 4 APTIKOM, tidak dipakai dalam perhitungan (KD-22).
            // Bobot tingkat kepentingan menurut CC2020, berupa bilangan bulat kecil (mis. 2–6).
            $table->unsignedTinyInteger('bobot_min')->nullable();
            $table->unsignedTinyInteger('bobot_max')->nullable();
            // Jam menurut CS2023: null = BK tidak bersumber dari CS2023, 0 = memang 0 jam.
            $table->unsignedSmallInteger('cs_core')->nullable();
            $table->unsignedSmallInteger('ka_core')->nullable();
            $table->string('referensi')->nullable();
            $table->timestamps();

            $table->unique(['kurikulum_id', 'kode_bk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_kajian');
    }
};
