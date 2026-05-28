<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->string('person_id', 50);
            $table->foreignId('group_id')->constrained('monitoring_groups')->onDelete('cascade');
            $table->foreignId('camera_id')->nullable()->constrained('cameras')->onDelete('set null');
            $table->timestamp('waktu')->useCurrent();
            $table->enum('status', ['patuh', 'tidak_patuh']);
            $table->boolean('membawa_instrumen')->default(false);
            $table->boolean('aktivitas_cuci_tangan')->default(false);
            $table->text('snapshot_path')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->timestamps();

            // Index untuk query cepat
            $table->index(['waktu', 'status']);
            $table->index('group_id');
            $table->index('camera_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_logs');
    }
};
