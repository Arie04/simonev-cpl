<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurikulum', function (Blueprint $table) {
            $table->uuid('kurikulum_id')->primary();
            $table->foreignUuid('program_studi_id')->constrained('program_studi', 'program_studi_id')->restrictOnDelete();
            $table->string('nama_kurikulum');
            // Kurikulum terbaru = tahun_berlaku terbesar, bukan urutan id.
            $table->unsignedSmallInteger('tahun_berlaku')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurikulum');
    }
};
