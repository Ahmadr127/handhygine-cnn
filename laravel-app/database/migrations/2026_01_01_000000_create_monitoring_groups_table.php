<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_groups', function (Blueprint $table) {
            $table->id();
            $table->string('nama_grup', 100);
            $table->text('deskripsi')->nullable();
            $table->string('lokasi', 100)->nullable();
            $table->boolean('aktif')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_groups');
    }
};
