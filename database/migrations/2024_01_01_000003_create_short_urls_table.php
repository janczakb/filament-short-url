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
        Schema::create('short_urls', function (Blueprint $table) {
            $table->id();

            // Core
            $table->string('destination_type', 20)->default('single'); // single, split
            $table->text('destination_url')->nullable();
            $table->json('rotation_variants')->nullable();
            $table->string('url_key', 32)->unique()->index();
            $table->string('notes')->nullable();

            // Status & activation
            $table->boolean('is_enabled')->default(true)->index();
            $table->smallInteger('redirect_status_code')->default(302);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('max_visits')->nullable();
            $table->string('expiration_redirect_url')->nullable();

            // Behaviour
            $table->boolean('single_use')->default(false);
            $table->boolean('forward_query_params')->default(false);
            $table->boolean('auto_open_app_mobile')->default(false);

            // Security, Webhooks & QR Counters
            $table->string('password')->nullable();
            $table->boolean('show_warning_page')->default(false);
            $table->string('webhook_url', 2048)->nullable();
            $table->integer('qr_scans')->default(0);

            // Tracking — master switch
            $table->boolean('track_visits')->default(true);

            // Tracking — field-level granularity
            $table->boolean('track_ip_address')->default(true);
            $table->boolean('track_browser')->default(true);
            $table->boolean('track_browser_version')->default(true);
            $table->boolean('track_operating_system')->default(true);
            $table->boolean('track_operating_system_version')->default(true);
            $table->boolean('track_device_type')->default(true);
            $table->boolean('track_referer_url')->default(true);
            $table->boolean('track_browser_language')->default(true);

            // QR code options & branding logo
            $table->json('qr_options')->nullable();
            $table->string('qr_logo')->nullable();

            // Google Analytics 4 integration
            $table->string('ga_tracking_id', 50)->nullable();

            // Advanced targeting rules (AND/OR, Multi-filter)
            $table->json('targeting_rules')->nullable();

            // Denormalized counters for fast reads (updated atomically)
            $table->unsignedBigInteger('total_visits')->default(0);
            $table->unsignedBigInteger('unique_visits')->default(0);

            $table->timestamps();
        });

        Schema::create('short_url_pixel', function (Blueprint $table) {
            $table->foreignId('short_url_id')->constrained('short_urls')->cascadeOnDelete();
            $table->foreignId('pixel_id')->constrained('short_url_pixels')->cascadeOnDelete();
            $table->primary(['short_url_id', 'pixel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_url_pixel');
        Schema::dropIfExists('short_urls');
    }
};
