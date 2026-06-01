<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_url_visits', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('short_url_id')
                ->constrained('short_urls')
                ->cascadeOnDelete();

            // Visitor fingerprint
            $table->ipAddress('ip_address')->nullable();
            $table->string('ip_hash', 64)->nullable()->index(); // SHA-256 of IP for unique counting

            // Browser detection
            $table->string('browser', 100)->nullable();
            $table->string('browser_version', 50)->nullable();

            // OS detection
            $table->string('operating_system', 100)->nullable();
            $table->string('operating_system_version', 50)->nullable();

            // Device classification ('desktop', 'mobile', 'tablet', 'robot') — validated at PHP level
            $table->string('device_type', 20)->nullable()->index();

            // Traffic source
            $table->text('referer_url')->nullable();

            // Geo-location
            $table->string('country', 100)->nullable()->index();
            $table->char('country_code', 2)->nullable()->index();

            $table->timestamp('visited_at')->useCurrent()->index();

            // Composite indexes for performance on millions of visits
            $table->index(['short_url_id', 'ip_hash']);
            $table->index(['short_url_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_url_visits');
    }
};
