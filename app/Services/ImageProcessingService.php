<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;

class ImageProcessingService
{
    private StorageService $storageService;
    private ImageManager $imageManager;

    public function __construct(StorageService $storageService)
    {
        $this->storageService = $storageService;
        $this->imageManager = new ImageManager(new Driver());
    }

    public function processUploadedPhoto(string $filePath, string $photographerId): array
    {
        $image = $this->imageManager->read($filePath);

        // Récupérer les dimensions et métadonnées
        $width = $image->width();
        $height = $image->height();
        $fileSize = filesize($filePath);
        $format = pathinfo($filePath, PATHINFO_EXTENSION);

        // Stocker l'original
        $originalUrl = $this->storageService->storeOriginal($filePath, $photographerId);

        // Générer et stocker la preview avec watermark
        $previewPath = $this->generatePreviewWithWatermark($image, $filePath);
        $previewUrl = $this->storageService->storePreview($previewPath, $photographerId);

        // Générer et stocker le thumbnail
        $thumbnailPath = $this->generateThumbnail($image, $filePath);
        $thumbnailUrl = $this->storageService->storeThumbnail($thumbnailPath, $photographerId);

        // Extraire la palette de couleurs
        $colorPalette = $this->extractColorPalette($image);

        // Nettoyer les fichiers temporaires
        @unlink($previewPath);
        @unlink($thumbnailPath);

        return [
            'original_url' => $originalUrl,
            'preview_url' => $previewUrl,
            'thumbnail_url' => $thumbnailUrl,
            'width' => $width,
            'height' => $height,
            'file_size' => $fileSize,
            'format' => $format,
            'color_palette' => $colorPalette,
        ];
    }

    private function generatePreviewWithWatermark($image, string $originalPath): string
    {
        // Créer une copie pour la preview
        $preview = clone $image;

        // Redimensionner à une taille maximale (ex: 1200px de largeur)
        if ($preview->width() > 1200) {
            $preview->scale(width: 1200);
        }

        // Ajouter le watermark diagonal "Pouire"
        $preview = $this->addDiagonalWatermark($preview);

        // Sauvegarder
        $tempPath = sys_get_temp_dir() . '/' . uniqid('preview_') . '.jpg';
        $preview->save($tempPath, quality: 85);

        return $tempPath;
    }

    private function generateThumbnail($image, string $originalPath): string
    {
        // Créer une copie pour le thumbnail
        $thumbnail = clone $image;

        // Redimensionner en 400x300 (crop pour garder le ratio)
        $thumbnail->cover(400, 300);

        // Sauvegarder
        $tempPath = sys_get_temp_dir() . '/' . uniqid('thumb_') . '.jpg';
        $thumbnail->save($tempPath, quality: 80);

        return $tempPath;
    }

    private function addDiagonalWatermark($image)
    {
        $width = $image->width();
        $height = $image->height();

        // Créer un texte watermark "Pouire" répété en diagonal
        $watermarkText = 'Pouire';
        $fontSize = (int) ($width * 0.05); // 5% de la largeur
        $color = 'rgba(255, 255, 255, 0.3)'; // Blanc semi-transparent

        // Ajouter plusieurs watermarks en diagonal
        $spacing = 200;
        for ($y = -$height; $y < $height * 2; $y += $spacing) {
            for ($x = -$width; $x < $width * 2; $x += $spacing) {
                $image->text($watermarkText, $x, $y, function ($font) use ($fontSize, $color) {
                    $font->filename(public_path('fonts/Arial.ttf')); // Utiliser une police système
                    $font->size($fontSize);
                    $font->color($color);
                    $font->angle(-45); // Angle diagonal
                });
            }
        }

        return $image;
    }

    private function extractColorPalette($image): array
    {
        // Redimensionner pour analyse plus rapide
        $temp = clone $image;
        $temp->scale(width: 100);

        // Récupérer les pixels dominants (simplifié)
        // Note: L'extraction complète de palette nécessiterait une bibliothèque supplémentaire
        // Pour l'instant, on retourne un tableau vide ou des couleurs par défaut
        $colors = [];

        // TODO: Implémenter une vraie extraction de palette de couleurs
        // Peut utiliser une bibliothèque comme ColorThief ou un algorithme K-means

        return $colors;
    }

    public function extractExifData(string $filePath): array
    {
        $exif = @exif_read_data($filePath);

        if (!$exif) {
            return [];
        }

        return [
            'camera' => $exif['Model'] ?? null,
            'lens' => $exif['LensModel'] ?? null,
            'iso' => $exif['ISOSpeedRatings'] ?? null,
            'aperture' => isset($exif['FNumber']) ? 'f/' . $exif['FNumber'] : null,
            'shutter_speed' => $exif['ExposureTime'] ?? null,
            'focal_length' => isset($exif['FocalLength']) ? (int) $exif['FocalLength'] : null,
            'taken_at' => isset($exif['DateTimeOriginal']) ?
                \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']) : null,
        ];
    }

    public function getOrientation(int $width, int $height): string
    {
        if ($width > $height) {
            return 'landscape';
        } elseif ($height > $width) {
            return 'portrait';
        } else {
            return 'square';
        }
    }
}
