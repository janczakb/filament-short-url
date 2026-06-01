<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            $table->string('password', 255)->nullable();
            $table->boolean('show_warning_page')->default(false);
            $table->json('targeting_rules')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('short_urls', function (Blueprint $table): void {
            $table->dropColumn([
                'password',
                'show_warning_page',
                'targeting_rules',
            ]);
        });
    }
};

