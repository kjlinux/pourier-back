<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->photographer = User::factory()->create();
    $this->category = Category::factory()->create();
    Storage::fake('local');
});

it('validates required fields for photo upload', function () {
    $response = $this->actingAs($this->photographer)
        ->postJson('/api/photographer/photos', []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['photos', 'category_id', 'tags', 'price_standard']);
});

it('validates photo file format', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1000);

    $response = $this->actingAs($this->photographer)
        ->postJson('/api/photographer/photos', [
            'photos' => [$file],
            'category_id' => $this->category->id,
            'tags' => ['nature', 'landscape', 'mountains'],
            'price_standard' => 10,
            'price_extended' => 25,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['photos.0']);
});

it('validates photo file size limit', function () {
    $file = UploadedFile::fake()->image('photo.jpg')->size(51 * 1024); // 51 MB

    $response = $this->actingAs($this->photographer)
        ->postJson('/api/photographer/photos', [
            'photos' => [$file],
            'category_id' => $this->category->id,
            'tags' => ['nature', 'landscape', 'mountains'],
            'price_standard' => 10,
            'price_extended' => 25,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['photos.0']);
});

it('validates tags count', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 1920, 1080);

    $response = $this->actingAs($this->photographer)
        ->postJson('/api/photographer/photos', [
            'photos' => [$file],
            'category_id' => $this->category->id,
            'tags' => ['nature', 'landscape'], // Less than 3
            'price_standard' => 10,
            'price_extended' => 25,
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['tags']);
});

it('validates extended price is at least 2x standard price', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 1920, 1080);

    $response = $this->actingAs($this->photographer)
        ->postJson('/api/photographer/photos', [
            'photos' => [$file],
            'category_id' => $this->category->id,
            'tags' => ['nature', 'landscape', 'mountains'],
            'price_standard' => 10,
            'price_extended' => 15, // Less than 2x standard
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['price_extended']);
});

it('creates photo records with pending status', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 1920, 1080);

    $response = $this->actingAs($this->photographer)
        ->postJson('/api/photographer/photos', [
            'photos' => [$file],
            'category_id' => $this->category->id,
            'tags' => ['nature', 'landscape', 'mountains'],
            'price_standard' => 10,
            'price_extended' => 25,
            'title' => 'Beautiful Landscape',
            'description' => 'A stunning mountain landscape',
        ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'message',
        'photos' => [
            '*' => ['id', 'title', 'status'],
        ],
    ]);

    $this->assertDatabaseHas('photos', [
        'photographer_id' => $this->photographer->id,
        'category_id' => $this->category->id,
        'title' => 'Beautiful Landscape',
        'status' => 'pending',
    ]);
});
