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
        Schema::table('photos', function (Blueprint $table) {
            $table->string('original_url')->nullable()->change();
            $table->string('preview_url')->nullable()->change();
            $table->string('thumbnail_url')->nullable()->change();
            $table->integer('width')->nullable()->change();
            $table->integer('height')->nullable()->change();
            $table->bigInteger('file_size')->nullable()->change();
            $table->string('format')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('original_url')->nullable(false)->change();
            $table->string('preview_url')->nullable(false)->change();
            $table->string('thumbnail_url')->nullable(false)->change();
            $table->integer('width')->nullable(false)->change();
            $table->integer('height')->nullable(false)->change();
            $table->bigInteger('file_size')->nullable(false)->change();
            $table->string('format')->nullable(false)->change();
        });
    }
};
