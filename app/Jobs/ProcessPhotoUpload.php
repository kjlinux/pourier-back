<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Services\ImageProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessPhotoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(
        private string $tempPath,
        private string $photographerId,
        private array $metadata
    ) {}

    public function handle(ImageProcessingService $imageProcessingService): void
    {
        try {
            // Traiter l'image
            $result = $imageProcessingService->processUploadedPhoto(
                Storage::path($this->tempPath),
                $this->photographerId
            );

            // Mettre à jour la photo dans la BDD
            $photo = Photo::where('photographer_id', $this->photographerId)
                ->where('status', 'pending')
                ->whereNull('original_url')
                ->latest()
                ->first();

            if ($photo) {
                $photo->update([
                    'original_url' => $result['original_url'],
                    'preview_url' => $result['preview_url'],
                    'thumbnail_url' => $result['thumbnail_url'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'file_size' => $result['file_size'],
                    'format' => $result['format'],
                    'color_palette' => $result['color_palette'],
                ]);

                // Dispatcher le job d'extraction EXIF
                ExtractExifData::dispatch($photo);
            }

            // Nettoyer le fichier temporaire
            Storage::delete($this->tempPath);

        } catch (\Exception $e) {
            \Log::error('Erreur traitement photo: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Échec traitement photo: ' . $exception->getMessage());
        Storage::delete($this->tempPath);
    }
}
