<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->string('external_id')->nullable()->unique();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('ref')->nullable();
            $table->boolean('public_stats_enabled')->default(false);
            $table->string('public_stats_password')->nullable();
        });

        Schema::table('short_url_visits', function (Blueprint $table) {
            $table->index(['short_url_id', 'ip_hash'], 'short_url_visits_url_ip_hash_index');
        });
    }

    public function down(): void
    {
        Schema::table('short_url_visits', function (Blueprint $table) {
            $table->dropIndex('short_url_visits_url_ip_hash_index');
        });

        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropColumn([
                'external_id',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'ref',
                'public_stats_enabled',
                'public_stats_password',
            ]);
        });
    }
};
