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
        Schema::create('short_url_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->index();
            $table->string('color')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('short_url_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->index();
            $table->string('color')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('short_url_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('short_url_id')->index();
            $table->unsignedBigInteger('tag_id')->index();
            $table->primary(['short_url_id', 'tag_id']);
        });

        Schema::table('short_urls', function (Blueprint $table) {
            $table->unsignedBigInteger('folder_id')->nullable()->after('custom_domain_id')->index();
            $table->boolean('is_archived')->default(false)->after('is_enabled')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table) {
            $table->dropColumn(['folder_id', 'is_archived']);
        });

        Schema::dropIfExists('short_url_tag');
        Schema::dropIfExists('short_url_tags');
        Schema::dropIfExists('short_url_folders');
    }
};
