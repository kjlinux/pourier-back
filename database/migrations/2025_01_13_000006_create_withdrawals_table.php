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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('photographer_id')->constrained('users')->onDelete('cascade');

            // Amount (in FCFA, stored as integer)
            $table->unsignedBigInteger('amount'); // minimum 5000 FCFA

            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected', 'cancelled'])->default('pending');

            // Payment details
            $table->enum('payment_method', ['mobile_money', 'bank_transfer']);
            $table->json('payment_details'); // {operator: 'orange', phone: '+226...', etc.}

            // Processing
            $table->text('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('photographer_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
