<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained('monitoring_groups')->onDelete('set null');
            $table->string('nama_kamera', 100);
            $table->enum('tipe', ['usb', 'rtsp', 'file']);
            $table->text('source');
            $table->boolean('aktif')->default(false);
            $table->json('zona_config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
