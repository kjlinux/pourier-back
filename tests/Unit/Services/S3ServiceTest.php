<?php

use App\Services\S3Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->s3Service = app(S3Service::class);
});

it('generates signed URL with default expiration from config', function () {
    Config::set('s3.signed_url_expiration.preview', 120);

    Storage::fake('s3');

    $url = $this->s3Service->getPreviewUrl('photo-123', 'test.jpg');

    expect($url)->toBeString();
});

it('generates signed URL with custom expiration', function () {
    Storage::fake('s3');

    $url = $this->s3Service->getPreviewUrl('photo-123', 'test.jpg', 30);

    expect($url)->toBeString();
});

it('generates preview URL with correct path format', function () {
    Storage::fake('s3');

    Storage::shouldReceive('disk')
        ->with('s3')
        ->andReturnSelf()
        ->shouldReceive('temporaryUrl')
        ->once()
        ->with('photos/photo-123/previews/test.jpg', \Mockery::any())
        ->andReturn('https://s3.amazonaws.com/bucket/photos/photo-123/previews/test.jpg?signature=xyz');

    $url = $this->s3Service->getPreviewUrl('photo-123', 'test.jpg');

    expect($url)->toContain('photos/photo-123/previews/test.jpg');
});

it('generates thumbnail URL with correct path format', function () {
    Storage::fake('s3');

    Storage::shouldReceive('disk')
        ->with('s3')
        ->andReturnSelf()
        ->shouldReceive('temporaryUrl')
        ->once()
        ->with('photos/photo-123/thumbnails/test.jpg', \Mockery::any())
        ->andReturn('https://s3.amazonaws.com/bucket/photos/photo-123/thumbnails/test.jpg?signature=xyz');

    $url = $this->s3Service->getThumbnailUrl('photo-123', 'test.jpg');

    expect($url)->toContain('photos/photo-123/thumbnails/test.jpg');
});

it('generates original URL with correct path format', function () {
    Storage::fake('s3');

    Storage::shouldReceive('disk')
        ->with('s3')
        ->andReturnSelf()
        ->shouldReceive('temporaryUrl')
        ->once()
        ->with('photos/photo-123/originals/test.jpg', \Mockery::any())
        ->andReturn('https://s3.amazonaws.com/bucket/photos/photo-123/originals/test.jpg?signature=xyz');

    $url = $this->s3Service->getOriginalUrl('photo-123', 'test.jpg');

    expect($url)->toContain('photos/photo-123/originals/test.jpg');
});

it('generates avatar URL with correct path format', function () {
    Storage::fake('s3');

    Storage::shouldReceive('disk')
        ->with('s3')
        ->andReturnSelf()
        ->shouldReceive('temporaryUrl')
        ->once()
        ->with('avatars/user-123/avatar.jpg', \Mockery::any())
        ->andReturn('https://s3.amazonaws.com/bucket/avatars/user-123/avatar.jpg?signature=xyz');

    $url = $this->s3Service->getAvatarUrl('user-123', 'avatar.jpg');

    expect($url)->toContain('avatars/user-123/avatar.jpg');
});

it('generates invoice URL with correct path format', function () {
    Storage::fake('s3');

    Storage::shouldReceive('disk')
        ->with('s3')
        ->andReturnSelf()
        ->shouldReceive('temporaryUrl')
        ->once()
        ->with('invoices/order-123/invoice.pdf', \Mockery::any())
        ->andReturn('https://s3.amazonaws.com/bucket/invoices/order-123/invoice.pdf?signature=xyz');

    $url = $this->s3Service->getInvoiceUrl('order-123', 'invoice.pdf');

    expect($url)->toContain('invoices/order-123/invoice.pdf');
});

it('checks if file exists on S3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('test-file.jpg', 'content');

    $exists = $this->s3Service->fileExists('test-file.jpg');

    expect($exists)->toBeTrue();
});

it('returns false when file does not exist on S3', function () {
    Storage::fake('s3');

    $exists = $this->s3Service->fileExists('non-existent-file.jpg');

    expect($exists)->toBeFalse();
});

it('deletes a file from S3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('test-file.jpg', 'content');

    $deleted = $this->s3Service->deleteFile('test-file.jpg');

    expect($deleted)->toBeTrue();
    expect(Storage::disk('s3')->exists('test-file.jpg'))->toBeFalse();
});

it('deletes entire photo directory from S3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('photos/photo-123/originals/test.jpg', 'content');
    Storage::disk('s3')->put('photos/photo-123/previews/test.jpg', 'content');
    Storage::disk('s3')->put('photos/photo-123/thumbnails/test.jpg', 'content');

    $deleted = $this->s3Service->deletePhoto('photo-123');

    expect($deleted)->toBeTrue();
    expect(Storage::disk('s3')->exists('photos/photo-123/originals/test.jpg'))->toBeFalse();
    expect(Storage::disk('s3')->exists('photos/photo-123/previews/test.jpg'))->toBeFalse();
    expect(Storage::disk('s3')->exists('photos/photo-123/thumbnails/test.jpg'))->toBeFalse();
});

it('gets file size from S3', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('test-file.jpg', 'test content here');

    $size = $this->s3Service->getFileSize('test-file.jpg');

    expect($size)->toBeGreaterThan(0);
});
