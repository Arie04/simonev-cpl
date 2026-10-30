<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->uuid('mata_kuliah_id')->primary();
            $table->foreignUuid('kurikulum_id')->constrained('kurikulum', 'kurikulum_id')->restrictOnDelete();
            $table->string('kode_mata_kuliah', 20);
            $table->string('nama_mata_kuliah');
            $table->unsignedTinyInteger('semester');
            $table->unsignedTinyInteger('jumlah_sks');
            $table->string('jenis_mata_kuliah', 50);
            $table->string('level_bloom_mk', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['kurikulum_id', 'kode_mata_kuliah']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_kuliah');
    }
};
