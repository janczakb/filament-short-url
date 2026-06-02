<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_url_visits', function (Blueprint $table): void {
            $table->boolean('is_bot')->default(false)->index();
            $table->boolean('is_proxy')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('short_url_visits', function (Blueprint $table): void {
            $table->dropColumn(['is_bot', 'is_proxy']);
        });
    }
};
