<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skips an existing table, so a site that already has its own `pages` table keeps it as is.
     */
    public function up(): void
    {
        if (Schema::hasTable('pages')) {
            return;
        }

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('path')->unique();
            $table->string('template');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_nav')->default(false);
            $table->string('nav_anchor')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('hero_heading')->nullable();
            $table->string('hero_subheading', 500)->nullable();
            $table->string('hero_image')->nullable();
            $table->string('og_image')->nullable();
            $table->string('section_eyebrow')->nullable();
            $table->string('section_heading')->nullable();
            $table->text('body')->nullable();
            $table->json('bullets')->nullable();
            $table->json('buttons')->nullable();
            $table->string('circle_image')->nullable();
            $table->string('circle_image_alt')->nullable();
            $table->string('card_excerpt', 500)->nullable();
            $table->string('card_image')->nullable();
            $table->string('cta_eyebrow')->nullable();
            $table->string('cta_heading')->nullable();
            $table->string('cta_button_label')->nullable();
            $table->timestamps();

            $table->unique(['parent_id', 'slug']);
        });
    }

    /**
     * Deliberately leaves the table alone: up() may have skipped a site's existing table, and
     * rolling back must never drop its content.
     */
    public function down(): void {}
};
