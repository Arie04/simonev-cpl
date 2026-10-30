<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->uuid('mahasiswa_id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users', 'user_id')->restrictOnDelete();
            $table->string('nim', 20)->unique();
            $table->string('nama');
            $table->unsignedSmallInteger('angkatan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
