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
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('photo_id')->constrained('photos')->onDelete('restrict');
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('restrict');

            // Photo info (snapshot at purchase time)
            $table->string('photo_title');
            $table->enum('license_type', ['standard', 'extended'])->default('standard');

            // Amounts (in FCFA, stored as integer)
            $table->unsignedBigInteger('price'); // Price paid by buyer
            $table->unsignedBigInteger('photographer_amount'); // 80% for photographer
            $table->unsignedBigInteger('commission'); // 20% for platform

            // Download
            $table->string('download_url')->nullable(); // Signed S3 URL for download
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('download_expires_at')->nullable(); // URL expiration

            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('photo_id');
            $table->index('photographer_id');
            $table->index('license_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
