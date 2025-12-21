<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default');
    }

    /**
     * Get the high resolution path for a photo (original file)
     */
    public function getHighResPath(\App\Models\Photo $photo): string
    {
        // Extract path from the original_url stored in database
        return $this->extractPathFromUrl($photo->original_url);
    }

    /**
     * Get the watermark/preview path for a photo
     */
    public function getWatermarkPath(\App\Models\Photo $photo): string
    {
        // Extract path from the preview_url stored in database
        return $this->extractPathFromUrl($photo->preview_url);
    }

    /**
     * Get the preview path for a photo
     */
    public function getPreviewPath(\App\Models\Photo $photo): string
    {
        return $this->extractPathFromUrl($photo->preview_url);
    }

    /**
     * Get the thumbnail path for a photo
     */
    public function getThumbnailPath(\App\Models\Photo $photo): string
    {
        return $this->extractPathFromUrl($photo->thumbnail_url);
    }

    /**
     * Extract the storage path from a URL
     * Made public to be accessible from controllers
     */
    public function extractPathFromUrl(?string $url): string
    {
        if (! $url) {
            return '';
        }

        // If it's already a path (not a URL), return as-is
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Handle S3 URLs - more robust detection
        $awsBucket = config('filesystems.disks.s3.bucket');
        $isS3Url = str_contains($url, 's3.amazonaws.com') ||
                   str_contains($url, '.s3.') ||
                   ($awsBucket && preg_match("#/{$awsBucket}/#", $url));

        if ($isS3Url) {
            $parsed = parse_url($url);
            $path = ltrim($parsed['path'], '/');

            // Remove bucket name if present in path
            if ($awsBucket && str_starts_with($path, $awsBucket.'/')) {
                $path = substr($path, strlen($awsBucket) + 1);
            }

            return $path;
        }

        // Handle local URLs
        $path = parse_url($url, PHP_URL_PATH);

        return ltrim($path, '/');
    }

    public function storeOriginal(string $filePath, string $photographerId): string
    {
        $filename = $this->generateFilename('original');
        $path = "photos/{$photographerId}/originals/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk($this->disk)->put($path, $content, [
            'visibility' => 'private',
            'CacheControl' => 'max-age=31536000',
        ]);

        return $path; // Return path instead of URL for S3 signed URL generation
    }

    public function storePreview(string $filePath, string $photographerId): string
    {
        $filename = $this->generateFilename('preview');
        $path = "photos/{$photographerId}/previews/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk($this->disk)->put($path, $content, [
            'visibility' => 'private',
            'CacheControl' => 'max-age=31536000',
        ]);

        return $path; // Return path instead of URL for S3 signed URL generation
    }

    public function storeThumbnail(string $filePath, string $photographerId): string
    {
        $filename = $this->generateFilename('thumb');
        $path = "photos/{$photographerId}/thumbnails/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk($this->disk)->put($path, $content, [
            'visibility' => 'private',
            'CacheControl' => 'max-age=31536000',
        ]);

        return $path; // Return path instead of URL for S3 signed URL generation
    }

    public function storeAvatar(string $filePath, string $userId): string
    {
        $filename = $this->generateFilename('avatar');
        $path = "users/{$userId}/avatars/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk($this->disk)->put($path, $content, [
            'visibility' => 'private',
            'CacheControl' => 'max-age=31536000',
        ]);

        return $path; // Return path instead of URL for S3 signed URL generation
    }

    public function storeCover(string $filePath, string $userId): string
    {
        $filename = $this->generateFilename('cover');
        $path = "users/{$userId}/covers/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk($this->disk)->put($path, $content, [
            'visibility' => 'private',
            'CacheControl' => 'max-age=31536000',
        ]);

        return $path; // Return path instead of URL for S3 signed URL generation
    }

    public function storeInvoice(string $content, string $orderNumber): string
    {
        $filename = "invoice-{$orderNumber}.pdf";
        $path = "invoices/{$filename}";

        Storage::disk($this->disk)->put($path, $content, [
            'visibility' => 'private',
            'CacheControl' => 'max-age=0',
        ]);

        return $path; // Return path instead of URL for S3 signed URL generation
    }

    public function generateSignedDownloadUrl(string $url, int $expirationHours = 24): string
    {
        // Extract path from URL
        $path = $this->extractPathFromUrl($url);

        // Generate temporary signed URL
        return Storage::disk($this->disk)->temporaryUrl(
            $path,
            now()->addHours($expirationHours)
        );
    }

    public function deleteFile(string $url): bool
    {
        try {
            $path = $this->extractPathFromUrl($url);

            return Storage::disk($this->disk)->delete($path);
        } catch (\Exception $e) {
            \Log::error('File deletion error: '.$e->getMessage());

            return false;
        }
    }

    private function generateFilename(string $prefix): string
    {
        return $prefix.'-'.Str::uuid().'-'.time().'.jpg';
    }
}
