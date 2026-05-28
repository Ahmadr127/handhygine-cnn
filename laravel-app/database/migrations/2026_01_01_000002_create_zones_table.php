<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('monitoring_groups')->onDelete('cascade');
            $table->foreignId('camera_id')->nullable()->constrained('cameras')->onDelete('cascade');
            $table->string('nama_zona', 50);
            $table->enum('tipe_zona', ['sanitizer', 'wastafel', 'pintu']);
            $table->json('polygon_points');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
