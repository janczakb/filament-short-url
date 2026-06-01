<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_urls', function (Blueprint $table): void {
            $table->id();

            // Core
            $table->text('destination_url');
            $table->string('url_key', 32)->unique()->index();
            $table->string('notes')->nullable();

            // Status & activation
            $table->boolean('is_enabled')->default(true)->index();
            $table->smallInteger('redirect_status_code')->default(302);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            // Behaviour
            $table->boolean('single_use')->default(false);
            $table->boolean('forward_query_params')->default(false);

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

            // QR code design (JSON blob)
            $table->json('qr_options')->nullable();

            // Google Analytics 4 integration
            $table->string('ga_tracking_id', 50)->nullable();

            // Denormalized counters for fast reads (updated atomically)
            $table->unsignedBigInteger('total_visits')->default(0);
            $table->unsignedBigInteger('unique_visits')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_urls');
    }
};
