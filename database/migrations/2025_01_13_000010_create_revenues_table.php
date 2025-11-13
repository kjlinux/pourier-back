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
        Schema::create('revenues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('cascade');
            $table->date('month'); // First day of the month (2025-01-01, 2025-02-01, etc.)

            // Amounts (in FCFA, stored as integer)
            $table->unsignedBigInteger('total_sales')->default(0); // Total sales amount
            $table->unsignedBigInteger('commission')->default(0); // 20% platform commission
            $table->unsignedBigInteger('net_revenue')->default(0); // 80% photographer revenue
            $table->unsignedBigInteger('available_balance')->default(0); // Amount available for withdrawal (after 30-day security period)
            $table->unsignedBigInteger('pending_balance')->default(0); // Amount still in 30-day security period
            $table->unsignedBigInteger('withdrawn')->default(0); // Amount already withdrawn

            // Stats
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedInteger('photos_sold')->default(0); // Unique photos sold

            $table->timestamps();

            // Unique constraint: one record per photographer per month
            $table->unique(['photographer_id', 'month']);

            // Indexes
            $table->index('photographer_id');
            $table->index('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
