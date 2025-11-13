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
        Schema::create('photographer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('username', 50)->unique();
            $table->string('display_name', 100)->nullable();
            $table->string('cover_photo_url')->nullable();
            $table->string('location', 100)->nullable();
            $table->string('website')->nullable();
            $table->string('instagram', 50)->nullable();
            $table->string('portfolio_url')->nullable();
            $table->json('specialties')->nullable(); // Array of specialties
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(80.00); // 80% for photographer
            $table->unsignedBigInteger('total_sales')->default(0); // in FCFA
            $table->unsignedBigInteger('total_revenue')->default(0); // in FCFA
            $table->unsignedInteger('followers_count')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('username');
            $table->index('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photographer_profiles');
    }
};
