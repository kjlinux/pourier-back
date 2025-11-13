<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('photographer_paid')->default(false)->after('photographer_amount');
            $table->timestamp('photographer_paid_at')->nullable()->after('photographer_paid');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['photographer_paid', 'photographer_paid_at']);
        });
    }
};
