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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained('carts')->onDelete('cascade');
            $table->foreignUuid('photo_id')->constrained('photos')->onDelete('cascade');
            $table->enum('license_type', ['standard', 'extended']);
            $table->decimal('price', 10, 2);
            $table->timestamps();

            // Ensure a photo can only be added once per cart with the same license type
            $table->unique(['cart_id', 'photo_id', 'license_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
