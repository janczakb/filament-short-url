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
        Schema::table('short_urls', function (Blueprint $table) {
            $table->string('pixel_meta_id', 100)->nullable();
            $table->string('pixel_google_id', 100)->nullable();
            $table->string('pixel_linkedin_id', 100)->nullable();
            $table->string('webhook_url', 2048)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropColumn([
                'pixel_meta_id',
                'pixel_google_id',
                'pixel_linkedin_id',
                'webhook_url',
            ]);
        });
    }
};
