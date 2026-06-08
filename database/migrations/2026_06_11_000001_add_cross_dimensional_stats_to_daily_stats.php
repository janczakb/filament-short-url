<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_url_daily_stats', function (Blueprint $table) {
            $table->json('cross_dimensional_stats')->nullable();
            $table->json('cross_filter_pairs')->nullable();
            $table->json('filter_qr_counts')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('short_url_daily_stats', function (Blueprint $table) {
            $table->dropColumn(['cross_dimensional_stats', 'cross_filter_pairs', 'filter_qr_counts']);
        });
    }
};
