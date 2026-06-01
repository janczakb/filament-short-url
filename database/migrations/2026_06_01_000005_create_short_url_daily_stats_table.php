<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_url_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('short_url_id')
                ->constrained('short_urls')
                ->cascadeOnDelete();
            $table->date('date');
            $table->integer('visits_count')->default(0);
            $table->integer('unique_visits_count')->default(0);
            $table->json('device_stats')->nullable();
            $table->json('browser_stats')->nullable();
            $table->json('os_stats')->nullable();
            $table->json('country_stats')->nullable();
            $table->json('city_stats')->nullable();
            $table->json('referer_stats')->nullable();
            $table->json('utm_source_stats')->nullable();
            $table->json('utm_medium_stats')->nullable();
            $table->json('utm_campaign_stats')->nullable();
            $table->timestamps();

            $table->unique(['short_url_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_url_daily_stats');
    }
};
