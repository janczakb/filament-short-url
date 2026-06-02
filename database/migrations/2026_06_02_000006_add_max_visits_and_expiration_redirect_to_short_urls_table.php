<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            $table->unsignedInteger('max_visits')->nullable();
            $table->string('expiration_redirect_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            $table->dropColumn(['max_visits', 'expiration_redirect_url']);
        });
    }
};
