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
            // Ajouter la colonne ligdicash_token
            $table->string('ligdicash_token')->nullable()->after('payment_id');

            // Supprimer la colonne cinetpay_transaction_id si elle existe
            if (Schema::hasColumn('orders', 'cinetpay_transaction_id')) {
                $table->dropColumn('cinetpay_transaction_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Supprimer la colonne ligdicash_token
            $table->dropColumn('ligdicash_token');

            // Ré-ajouter la colonne cinetpay_transaction_id
            $table->string('cinetpay_transaction_id')->nullable()->after('payment_id');
        });
    }
};
