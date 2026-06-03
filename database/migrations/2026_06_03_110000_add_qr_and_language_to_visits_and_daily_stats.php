<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            $table->integer('qr_scans')->default(0);
        });

        Schema::table('short_url_visits', function (Blueprint $table): void {
            $table->boolean('is_qr_scan')->default(false)->index();
            $table->string('browser_language', 10)->nullable()->index();
        });

        Schema::table('short_url_daily_stats', function (Blueprint $table): void {
            $table->integer('qr_visits_count')->default(0);
            $table->json('language_stats')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            $table->dropColumn('qr_scans');
        });

        Schema::table('short_url_visits', function (Blueprint $table): void {
            $table->dropIndex(['is_qr_scan']);
            $table->dropColumn('is_qr_scan');
            $table->dropIndex(['browser_language']);
            $table->dropColumn('browser_language');
        });

        Schema::table('short_url_daily_stats', function (Blueprint $table): void {
            $table->dropColumn(['qr_visits_count', 'language_stats']);
        });
    }
};
