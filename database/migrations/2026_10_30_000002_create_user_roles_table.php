<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('user_role_id')->primary();
            // Ikut terhapus bersama akunnya; peran yang masih dipakai akun tidak bisa dihapus (KD-20).
            $table->foreignUuid('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles', 'role_id')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
