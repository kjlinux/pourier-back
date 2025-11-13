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
            $table->string('order_number', 50)->unique(); // ORD-20251113-ABC123
            $table->foreignUuid('user_id')->constrained('users')->onDelete('restrict');

            // Amounts (in FCFA, stored as integer)
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('total');

            // Payment
            $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable(); // mobile_money, card
            $table->string('payment_id')->nullable(); // CinetPay payment ID
            $table->string('cinetpay_transaction_id')->nullable();

            // Billing info
            $table->string('billing_email');
            $table->string('billing_first_name', 50);
            $table->string('billing_last_name', 50);
            $table->string('billing_phone', 20)->nullable();

            // Invoice
            $table->string('invoice_url')->nullable(); // S3 URL for PDF invoice
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('order_number');
            $table->index('user_id');
            $table->index('payment_status');
            $table->index('cinetpay_transaction_id');
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
