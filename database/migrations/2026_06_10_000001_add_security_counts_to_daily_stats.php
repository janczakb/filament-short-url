<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_url_daily_stats', function (Blueprint $table) {
            $table->unsignedInteger('all_visits_count')->default(0)->after('visits_count');
            $table->unsignedInteger('bot_visits_count')->default(0)->after('all_visits_count');
            $table->unsignedInteger('proxy_visits_count')->default(0)->after('bot_visits_count');
        });
    }

    public function down(): void
    {
        Schema::table('short_url_daily_stats', function (Blueprint $table) {
            $table->dropColumn(['all_visits_count', 'bot_visits_count', 'proxy_visits_count']);
        });
    }
};
