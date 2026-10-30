<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metode_penilaian', function (Blueprint $table) {
            $table->uuid('metode_penilaian_id')->primary();
            $table->string('kode_metode', 20)->unique();
            $table->string('nama_metode', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metode_penilaian');
    }
};
