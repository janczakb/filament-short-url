<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('short_url_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_url_id')->constrained('short_urls')->cascadeOnDelete();

            // Client info
            $table->ipAddress('ip_address')->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('browser_version', 50)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->string('operating_system_version', 50)->nullable();
            $table->string('device_type', 50)->nullable()->index();
            $table->string('browser_language', 10)->nullable();

            // Referrer
            $table->text('referer_url')->nullable();
            $table->string('referer_host', 150)->nullable()->index();

            // Marketing UTM Tracing
            $table->string('utm_source', 100)->nullable()->index();
            $table->string('utm_medium', 100)->nullable()->index();
            $table->string('utm_campaign', 100)->nullable()->index();
            $table->string('utm_term', 100)->nullable();
            $table->string('utm_content', 100)->nullable();

            // Context flags
            $table->boolean('is_qr_scan')->default(false)->index();
            $table->boolean('is_bot')->default(false)->index();
            $table->boolean('is_proxy')->default(false)->index();

            // A/B Testing chosen variant label/URL
            $table->string('selected_variant', 255)->nullable()->index();

            $table->timestamp('visited_at')->useCurrent()->index();
            $table->index(['short_url_id', 'is_bot', 'is_proxy', 'visited_at'], 'visits_performance_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_url_visits');
    }
};
