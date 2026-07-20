<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_logs', function (Blueprint $table) {
            $table->enum('ground_truth', ['patuh', 'tidak_patuh'])
                ->nullable()
                ->after('status');
            $table->index('ground_truth');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_logs', function (Blueprint $table) {
            $table->dropIndex(['ground_truth']);
            $table->dropColumn('ground_truth');
        });
    }
};
