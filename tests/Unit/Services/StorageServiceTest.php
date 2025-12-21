<?php

use App\Services\StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('filesystems.default', 's3');
    Config::set('filesystems.disks.s3.bucket', 'test-bucket');
    $this->storageService = app(StorageService::class);
});

it('extracts path from S3 URL with s3.amazonaws.com', function () {
    $url = 'https://test-bucket.s3.amazonaws.com/photos/123/test.jpg';

    $path = $this->storageService->extractPathFromUrl($url);

    expect($path)->toBe('photos/123/test.jpg');
});

it('extracts path from S3 URL with .s3. subdomain', function () {
    $url = 'https://test-bucket.s3.us-east-1.amazonaws.com/photos/123/test.jpg';

    $path = $this->storageService->extractPathFromUrl($url);

    expect($path)->toBe('photos/123/test.jpg');
});

it('extracts path from S3 URL with bucket in path', function () {
    $url = 'https://s3.amazonaws.com/test-bucket/photos/123/test.jpg';

    $path = $this->storageService->extractPathFromUrl($url);

    expect($path)->toBe('photos/123/test.jpg');
});

it('extracts path from local URL', function () {
    $url = 'http://localhost/storage/photos/123/test.jpg';

    $path = $this->storageService->extractPathFromUrl($url);

    expect($path)->toBe('storage/photos/123/test.jpg');
});

it('returns path as-is when already a path', function () {
    $path = 'photos/123/test.jpg';

    $result = $this->storageService->extractPathFromUrl($path);

    expect($result)->toBe($path);
});

it('returns empty string for null URL', function () {
    $result = $this->storageService->extractPathFromUrl(null);

    expect($result)->toBe('');
});

it('returns empty string for empty URL', function () {
    $result = $this->storageService->extractPathFromUrl('');

    expect($result)->toBe('');
});

it('handles S3 URL without bucket name in path', function () {
    $url = 'https://test-bucket.s3.amazonaws.com/photos/123/test.jpg';

    $path = $this->storageService->extractPathFromUrl($url);

    expect($path)->not->toContain('test-bucket');
    expect($path)->toBe('photos/123/test.jpg');
});

it('deletes file successfully', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('photos/123/test.jpg', 'content');

    $deleted = $this->storageService->deleteFile('https://test-bucket.s3.amazonaws.com/photos/123/test.jpg');

    expect($deleted)->toBeTrue();
    expect(Storage::disk('s3')->exists('photos/123/test.jpg'))->toBeFalse();
});
