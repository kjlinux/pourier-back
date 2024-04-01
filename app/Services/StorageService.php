<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    private const DISK = 'local';

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
     */
    private function extractPathFromUrl(?string $url): string
    {
        if (!$url) {
            return '';
        }

        // If it's already a path (not a URL), return as-is
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Extract path from URL
        $path = parse_url($url, PHP_URL_PATH);
        return ltrim($path, '/');
    }

    public function storeOriginal(string $filePath, string $photographerId): string
    {
        $filename = $this->generateFilename('original');
        $path = "photos/{$photographerId}/originals/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk(self::DISK)->put($path, $content, 'private');

        return Storage::disk(self::DISK)->url($path);
    }

    public function storePreview(string $filePath, string $photographerId): string
    {
        $filename = $this->generateFilename('preview');
        $path = "photos/{$photographerId}/previews/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk(self::DISK)->put($path, $content, 'public');

        return Storage::disk(self::DISK)->url($path);
    }

    public function storeThumbnail(string $filePath, string $photographerId): string
    {
        $filename = $this->generateFilename('thumb');
        $path = "photos/{$photographerId}/thumbnails/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk(self::DISK)->put($path, $content, 'public');

        return Storage::disk(self::DISK)->url($path);
    }

    public function storeAvatar(string $filePath, string $userId): string
    {
        $filename = $this->generateFilename('avatar');
        $path = "users/{$userId}/avatars/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk(self::DISK)->put($path, $content, 'public');

        return Storage::disk(self::DISK)->url($path);
    }

    public function storeCover(string $filePath, string $userId): string
    {
        $filename = $this->generateFilename('cover');
        $path = "users/{$userId}/covers/{$filename}";

        $content = file_get_contents($filePath);
        Storage::disk(self::DISK)->put($path, $content, 'public');

        return Storage::disk(self::DISK)->url($path);
    }

    public function storeInvoice(string $content, string $orderNumber): string
    {
        $filename = "invoice-{$orderNumber}.pdf";
        $path = "invoices/{$filename}";

        Storage::disk(self::DISK)->put($path, $content, 'private');

        return Storage::disk(self::DISK)->url($path);
    }

    public function generateSignedDownloadUrl(string $url, int $expirationHours = 24): string
    {
        // Extraire le path depuis l'URL S3
        $path = parse_url($url, PHP_URL_PATH);
        $path = ltrim($path, '/');

        // Générer une URL signée temporaire
        return Storage::disk(self::DISK)->temporaryUrl(
            $path,
            now()->addHours($expirationHours)
        );
    }

    public function deleteFile(string $url): bool
    {
        try {
            $path = parse_url($url, PHP_URL_PATH);
            $path = ltrim($path, '/');

            return Storage::disk(self::DISK)->delete($path);
        } catch (\Exception $e) {
            \Log::error('Erreur suppression fichier S3: ' . $e->getMessage());
            return false;
        }
    }

    private function generateFilename(string $prefix): string
    {
        return $prefix . '-' . Str::uuid() . '-' . time() . '.jpg';
    }
}
