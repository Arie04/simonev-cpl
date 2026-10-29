<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profesi', function (Blueprint $table) {
            $table->uuid('profesi_id')->primary();
            $table->foreignUuid('kurikulum_id')->constrained('kurikulum', 'kurikulum_id')->restrictOnDelete();
            $table->string('nama_profesi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesi');
    }
};
