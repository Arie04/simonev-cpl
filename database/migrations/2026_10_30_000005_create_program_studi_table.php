<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_studi', function (Blueprint $table) {
            $table->uuid('program_studi_id')->primary();
            $table->string('nama_perguruan_tinggi');
            $table->string('nama_fakultas');
            $table->string('nama_program_studi');
            $table->string('izin_pendirian')->nullable();
            $table->string('peringkat_akreditasi', 50)->nullable();
            $table->string('jenjang_pendidikan', 50);
            $table->string('gelar_lulusan', 50);
            $table->text('visi_keilmuan')->nullable();
            $table->text('misi_program_studi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
