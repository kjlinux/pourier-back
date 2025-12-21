<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class S3Service
{
    /**
     * Generate a temporary signed URL for a file
     */
    public function getSignedUrl(string $path, int $expirationMinutes = 60): string
    {
        return Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Get signed URL for photo preview (with watermark)
     */
    public function getPreviewUrl(string $photoId, string $filename, ?int $expirationMinutes = null): string
    {
        $expirationMinutes = $expirationMinutes ?? config('s3.signed_url_expiration.preview', 60);
        $path = "photos/{$photoId}/previews/{$filename}";

        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for photo thumbnail
     */
    public function getThumbnailUrl(string $photoId, string $filename, ?int $expirationMinutes = null): string
    {
        $expirationMinutes = $expirationMinutes ?? config('s3.signed_url_expiration.thumbnail', 60);
        $path = "photos/{$photoId}/thumbnails/{$filename}";

        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for original photo (after payment)
     */
    public function getOriginalUrl(string $photoId, string $filename, ?int $expirationMinutes = null): string
    {
        $expirationMinutes = $expirationMinutes ?? config('s3.signed_url_expiration.original', 1440);
        $path = "photos/{$photoId}/originals/{$filename}";

        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for user avatar
     */
    public function getAvatarUrl(string $userId, string $filename, ?int $expirationMinutes = null): string
    {
        $expirationMinutes = $expirationMinutes ?? config('s3.signed_url_expiration.avatar', 1440);
        $path = "avatars/{$userId}/{$filename}";

        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for cover photo
     *
     * @param  string  $entityType  (event, album, etc.)
     */
    public function getCoverUrl(string $entityType, string $entityId, string $filename, ?int $expirationMinutes = null): string
    {
        $expirationMinutes = $expirationMinutes ?? config('s3.signed_url_expiration.cover', 1440);
        $path = "covers/{$entityType}/{$entityId}/{$filename}";

        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for invoice
     */
    public function getInvoiceUrl(string $orderId, string $filename, ?int $expirationMinutes = null): string
    {
        $expirationMinutes = $expirationMinutes ?? config('s3.signed_url_expiration.invoice', 60);
        $path = "invoices/{$orderId}/{$filename}";

        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Upload a file to S3
     *
     * @param  \Illuminate\Http\UploadedFile|string  $file
     * @return string|false The path of the uploaded file or false on failure
     */
    public function uploadFile($file, string $path, array $options = [])
    {
        $defaultOptions = [
            'visibility' => 'private',
        ];

        $options = array_merge($defaultOptions, $options);

        return Storage::disk('s3')->putFileAs(
            dirname($path),
            $file,
            basename($path),
            $options
        );
    }

    /**
     * Upload photo preview
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string|false
     */
    public function uploadPreview(string $photoId, $file, string $filename)
    {
        $path = "photos/{$photoId}/previews/{$filename}";

        return $this->uploadFile($file, $path);
    }

    /**
     * Upload photo thumbnail
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string|false
     */
    public function uploadThumbnail(string $photoId, $file, string $filename)
    {
        $path = "photos/{$photoId}/thumbnails/{$filename}";

        return $this->uploadFile($file, $path);
    }

    /**
     * Upload original photo
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string|false
     */
    public function uploadOriginal(string $photoId, $file, string $filename)
    {
        $path = "photos/{$photoId}/originals/{$filename}";

        return $this->uploadFile($file, $path);
    }

    /**
     * Delete a file from S3
     */
    public function deleteFile(string $path): bool
    {
        return Storage::disk('s3')->delete($path);
    }

    /**
     * Delete all versions of a photo
     */
    public function deletePhoto(string $photoId): bool
    {
        $directory = "photos/{$photoId}";

        return Storage::disk('s3')->deleteDirectory($directory);
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('s3')->exists($path);
    }

    /**
     * Get file size
     */
    public function getFileSize(string $path): int
    {
        return Storage::disk('s3')->size($path);
    }
}
