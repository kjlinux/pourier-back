<?php

use App\Models\Category;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->photographer = User::factory()->create();
    $this->category = Category::factory()->create();
});

it('redirects to S3 signed URL when downloading preview on S3', function () {
    Config::set('filesystems.default', 's3');
    Storage::fake('s3');

    $photo = Photo::factory()->create([
        'photographer_id' => $this->photographer->id,
        'category_id' => $this->category->id,
        'preview_url' => 'photos/123/previews/test.jpg',
        'status' => 'approved',
    ]);

    Storage::disk('s3')->put('photos/123/previews/test.jpg', 'preview content');

    $response = $this->get("/api/downloads/preview/{$photo->id}");

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('photos/123/previews/test.jpg');
});

it('returns 404 when preview URL is missing', function () {
    $photo = Photo::factory()->create([
        'photographer_id' => $this->photographer->id,
        'category_id' => $this->category->id,
        'preview_url' => null,
        'status' => 'approved',
    ]);

    $response = $this->get("/api/downloads/preview/{$photo->id}");

    $response->assertNotFound();
});

it('downloads preview file on local storage', function () {
    Config::set('filesystems.default', 'local');
    Storage::fake('local');

    $photo = Photo::factory()->create([
        'photographer_id' => $this->photographer->id,
        'category_id' => $this->category->id,
        'preview_url' => 'photos/123/previews/test.jpg',
        'title' => 'Test Photo',
        'status' => 'approved',
    ]);

    Storage::disk('local')->put('photos/123/previews/test.jpg', 'preview content');

    $response = $this->get("/api/downloads/preview/{$photo->id}");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'image/jpeg');
});
