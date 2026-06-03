<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create short_url_pixels table
        Schema::create('short_url_pixels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 50); // meta, google, linkedin, tiktok, pinterest
            $table->string('pixel_id', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'pixel_id']);
        });

        // 2. Create short_url_pixel pivot table
        Schema::create('short_url_pixel', function (Blueprint $table) {
            $table->foreignId('short_url_id')->constrained('short_urls')->cascadeOnDelete();
            $table->foreignId('pixel_id')->constrained('short_url_pixels')->cascadeOnDelete();
            $table->primary(['short_url_id', 'pixel_id']);
        });

        // 3. Data migration: Migrate existing pixel IDs from short_urls to short_url_pixels and pivot
        if (Schema::hasColumn('short_urls', 'pixel_meta_id')) {
            $oldUrls = DB::table('short_urls')
                ->whereNotNull('pixel_meta_id')
                ->orWhereNotNull('pixel_google_id')
                ->orWhereNotNull('pixel_linkedin_id')
                ->get();

            foreach ($oldUrls as $url) {
                // Migrate Meta Pixel
                if (! empty($url->pixel_meta_id)) {
                    $pixelId = DB::table('short_url_pixels')
                        ->where('type', 'meta')
                        ->where('pixel_id', $url->pixel_meta_id)
                        ->value('id');

                    if (! $pixelId) {
                        $pixelId = DB::table('short_url_pixels')->insertGetId([
                            'name' => 'Meta Pixel ('.$url->pixel_meta_id.')',
                            'type' => 'meta',
                            'pixel_id' => $url->pixel_meta_id,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('short_url_pixel')->insertOrIgnore([
                        'short_url_id' => $url->id,
                        'pixel_id' => $pixelId,
                    ]);
                }

                // Migrate Google Tag / GA4
                if (! empty($url->pixel_google_id)) {
                    $pixelId = DB::table('short_url_pixels')
                        ->where('type', 'google')
                        ->where('pixel_id', $url->pixel_google_id)
                        ->value('id');

                    if (! $pixelId) {
                        $pixelId = DB::table('short_url_pixels')->insertGetId([
                            'name' => 'Google Tag ('.$url->pixel_google_id.')',
                            'type' => 'google',
                            'pixel_id' => $url->pixel_google_id,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('short_url_pixel')->insertOrIgnore([
                        'short_url_id' => $url->id,
                        'pixel_id' => $pixelId,
                    ]);
                }

                // Migrate LinkedIn Insight
                if (! empty($url->pixel_linkedin_id)) {
                    $pixelId = DB::table('short_url_pixels')
                        ->where('type', 'linkedin')
                        ->where('pixel_id', $url->pixel_linkedin_id)
                        ->value('id');

                    if (! $pixelId) {
                        $pixelId = DB::table('short_url_pixels')->insertGetId([
                            'name' => 'LinkedIn Insight ('.$url->pixel_linkedin_id.')',
                            'type' => 'linkedin',
                            'pixel_id' => $url->pixel_linkedin_id,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('short_url_pixel')->insertOrIgnore([
                        'short_url_id' => $url->id,
                        'pixel_id' => $pixelId,
                    ]);
                }
            }

            // 4. Drop the old columns
            Schema::table('short_urls', function (Blueprint $table) {
                $table->dropColumn(['pixel_meta_id', 'pixel_google_id', 'pixel_linkedin_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add the columns
        Schema::table('short_urls', function (Blueprint $table) {
            $table->string('pixel_meta_id', 100)->nullable();
            $table->string('pixel_google_id', 100)->nullable();
            $table->string('pixel_linkedin_id', 100)->nullable();
        });

        // 2. Re-populate the old columns from pivot data
        $associations = DB::table('short_url_pixel')
            ->join('short_url_pixels', 'short_url_pixel.pixel_id', '=', 'short_url_pixels.id')
            ->select('short_url_pixel.short_url_id', 'short_url_pixels.type', 'short_url_pixels.pixel_id')
            ->get();

        foreach ($associations as $assoc) {
            if ($assoc->type === 'meta') {
                DB::table('short_urls')->where('id', $assoc->short_url_id)->update(['pixel_meta_id' => $assoc->pixel_id]);
            } elseif ($assoc->type === 'google') {
                DB::table('short_urls')->where('id', $assoc->short_url_id)->update(['pixel_google_id' => $assoc->pixel_id]);
            } elseif ($assoc->type === 'linkedin') {
                DB::table('short_urls')->where('id', $assoc->short_url_id)->update(['pixel_linkedin_id' => $assoc->pixel_id]);
            }
        }

        // 3. Drop tables
        Schema::dropIfExists('short_url_pixel');
        Schema::dropIfExists('short_url_pixels');
    }
};
