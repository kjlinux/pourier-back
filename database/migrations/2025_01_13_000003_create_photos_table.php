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
        Schema::create('photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('category_id')->constrained('categories')->onDelete('restrict');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('tags')->nullable(); // Array of tags

            // File URLs
            $table->string('original_url'); // S3 URL for original (full resolution)
            $table->string('preview_url'); // S3 URL with watermark
            $table->string('thumbnail_url'); // S3 URL for thumbnail

            // Image metadata
            $table->unsignedInteger('width'); // pixels
            $table->unsignedInteger('height'); // pixels
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('format', 10); // jpg, png, etc.
            $table->json('color_palette')->nullable(); // Array of dominant colors

            // EXIF data
            $table->string('camera')->nullable();
            $table->string('lens')->nullable();
            $table->string('iso')->nullable();
            $table->string('aperture')->nullable();
            $table->string('shutter_speed')->nullable();
            $table->string('focal_length')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->string('location')->nullable(); // Location where photo was taken

            // Pricing (in FCFA, stored as integer)
            $table->unsignedBigInteger('price_standard')->default(500); // minimum 500 FCFA
            $table->unsignedBigInteger('price_extended')->default(1000); // minimum 2x standard

            // Stats
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->unsignedInteger('sales_count')->default(0);

            // Moderation
            $table->boolean('is_public')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->foreignUuid('moderated_by')->nullable()->constrained('users')->onDelete('set null');

            // Featured
            $table->boolean('featured')->default(false);
            $table->timestamp('featured_until')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('photographer_id');
            $table->index('category_id');
            $table->index('status');
            $table->index(['is_public', 'status']);
            $table->index('featured');
            $table->index(['price_standard', 'price_extended']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
