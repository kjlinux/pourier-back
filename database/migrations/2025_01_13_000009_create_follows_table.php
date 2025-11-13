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
        Schema::create('follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('follower_id')->constrained('users')->onDelete('cascade'); // User who follows
            $table->foreignUuid('following_id')->constrained('users')->onDelete('cascade'); // Photographer being followed
            $table->timestamps();

            // Unique constraint: a user can follow another user only once
            $table->unique(['follower_id', 'following_id']);

            // Indexes
            $table->index('follower_id');
            $table->index('following_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
