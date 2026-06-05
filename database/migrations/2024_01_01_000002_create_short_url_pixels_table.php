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
        Schema::create('short_url_pixels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 50); // meta, google, linkedin, tiktok, pinterest
            $table->string('pixel_id', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'pixel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_url_pixels');
    }
};
