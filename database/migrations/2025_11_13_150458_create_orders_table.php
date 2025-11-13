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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number', 50)->unique(); // Format: ORD-YYYYMMDD-ABC123
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');

            // Pricing (in FCFA)
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total');

            // Payment
            $table->enum('payment_method', ['mobile_money', 'card']);
            $table->string('payment_provider')->nullable(); // ORANGE, MTN, MOOV, WAVE, VISA, etc.
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_id')->nullable(); // Transaction ID from payment provider
            $table->string('cinetpay_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Billing
            $table->string('billing_email');
            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_phone');

            // Invoice
            $table->string('invoice_url')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('order_number');
            $table->index('user_id');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
