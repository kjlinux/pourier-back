<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class S3Service
{
    /**
     * Generate a temporary signed URL for a file
     *
     * @param string $path
     * @param int $expirationMinutes
     * @return string
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
     *
     * @param string $photoId
     * @param string $filename
     * @param int $expirationMinutes
     * @return string
     */
    public function getPreviewUrl(string $photoId, string $filename, int $expirationMinutes = 60): string
    {
        $path = "photos/{$photoId}/previews/{$filename}";
        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for photo thumbnail
     *
     * @param string $photoId
     * @param string $filename
     * @param int $expirationMinutes
     * @return string
     */
    public function getThumbnailUrl(string $photoId, string $filename, int $expirationMinutes = 60): string
    {
        $path = "photos/{$photoId}/thumbnails/{$filename}";
        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for original photo (after payment)
     *
     * @param string $photoId
     * @param string $filename
     * @param int $expirationMinutes
     * @return string
     */
    public function getOriginalUrl(string $photoId, string $filename, int $expirationMinutes = 1440): string
    {
        $path = "photos/{$photoId}/originals/{$filename}";
        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for user avatar
     *
     * @param string $userId
     * @param string $filename
     * @param int $expirationMinutes
     * @return string
     */
    public function getAvatarUrl(string $userId, string $filename, int $expirationMinutes = 1440): string
    {
        $path = "avatars/{$userId}/{$filename}";
        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for cover photo
     *
     * @param string $entityType (event, album, etc.)
     * @param string $entityId
     * @param string $filename
     * @param int $expirationMinutes
     * @return string
     */
    public function getCoverUrl(string $entityType, string $entityId, string $filename, int $expirationMinutes = 1440): string
    {
        $path = "covers/{$entityType}/{$entityId}/{$filename}";
        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Get signed URL for invoice
     *
     * @param string $orderId
     * @param string $filename
     * @param int $expirationMinutes
     * @return string
     */
    public function getInvoiceUrl(string $orderId, string $filename, int $expirationMinutes = 60): string
    {
        $path = "invoices/{$orderId}/{$filename}";
        return $this->getSignedUrl($path, $expirationMinutes);
    }

    /**
     * Upload a file to S3
     *
     * @param \Illuminate\Http\UploadedFile|string $file
     * @param string $path
     * @param array $options
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
     * @param string $photoId
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $filename
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
     * @param string $photoId
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $filename
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
     * @param string $photoId
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $filename
     * @return string|false
     */
    public function uploadOriginal(string $photoId, $file, string $filename)
    {
        $path = "photos/{$photoId}/originals/{$filename}";
        return $this->uploadFile($file, $path);
    }

    /**
     * Delete a file from S3
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        return Storage::disk('s3')->delete($path);
    }

    /**
     * Delete all versions of a photo
     *
     * @param string $photoId
     * @return bool
     */
    public function deletePhoto(string $photoId): bool
    {
        $directory = "photos/{$photoId}";
        return Storage::disk('s3')->deleteDirectory($directory);
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('s3')->exists($path);
    }

    /**
     * Get file size
     *
     * @param string $path
     * @return int
     */
    public function getFileSize(string $path): int
    {
        return Storage::disk('s3')->size($path);
    }
}
