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

            // Snapshot data (at purchase time)
            $table->string('photo_title');
            $table->string('photo_thumbnail');
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('restrict');
            $table->string('photographer_name');

            // License & Pricing
            $table->enum('license_type', ['standard', 'extended']);
            $table->unsignedBigInteger('price'); // Price paid (in FCFA)
            $table->unsignedBigInteger('photographer_amount'); // 80% for photographer
            $table->unsignedBigInteger('platform_commission'); // 20% for platform

            // Download
            $table->string('download_url')->nullable();
            $table->timestamp('download_expires_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('photo_id');
            $table->index('photographer_id');
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
