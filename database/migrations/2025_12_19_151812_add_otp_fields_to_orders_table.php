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
        Schema::table('orders', function (Blueprint $table) {
            // OTP flow tracking
            $table->string('payment_phone', 20)->nullable()->after('billing_phone');
            $table->timestamp('otp_requested_at')->nullable()->after('payment_phone');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_requested_at');
            $table->unsignedTinyInteger('otp_request_count')->default(0)->after('otp_expires_at');
            $table->enum('payment_flow', ['redirect', 'otp'])->default('redirect')->after('payment_method');

            // Indexes for performance
            $table->index('payment_phone');
            $table->index('otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_phone']);
            $table->dropIndex(['otp_expires_at']);

            $table->dropColumn([
                'payment_phone',
                'otp_requested_at',
                'otp_expires_at',
                'otp_request_count',
                'payment_flow',
            ]);
        });
    }
};
