<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->unsignedBigInteger('domain_scope_id')->default(0);
        });

        DB::table('short_urls')->whereNotNull('custom_domain_id')->update([
            'domain_scope_id' => DB::raw('custom_domain_id'),
        ]);

        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropUnique(['url_key']);
            $table->unique(['url_key', 'domain_scope_id'], 'short_urls_url_key_domain_scope_unique');
            $table->index(['user_id', 'is_archived', 'id'], 'short_urls_user_archived_id_index');
        });

        Schema::table('short_urls', function (Blueprint $table) {
            $table->foreign('custom_domain_id')
                ->references('id')
                ->on('short_url_custom_domains')
                ->nullOnDelete();
        });

        Schema::table('short_url_visits', function (Blueprint $table) {
            $table->index(['short_url_id', 'id'], 'short_url_visits_url_id_index');
        });

        Schema::table('short_url_daily_stats', function (Blueprint $table) {
            $table->json('utm_terms')->nullable();
            $table->json('utm_contents')->nullable();
            $table->json('browser_versions')->nullable();
            $table->json('os_versions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('short_url_daily_stats', function (Blueprint $table) {
            $table->dropColumn(['utm_terms', 'utm_contents', 'browser_versions', 'os_versions']);
        });

        Schema::table('short_url_visits', function (Blueprint $table) {
            $table->dropIndex('short_url_visits_url_id_index');
        });

        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropForeign(['custom_domain_id']);
            $table->dropIndex('short_urls_user_archived_id_index');
            $table->dropUnique('short_urls_url_key_domain_scope_unique');
            $table->unique('url_key');
            $table->dropColumn('domain_scope_id');
        });
    }
};
