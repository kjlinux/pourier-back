<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtractExifData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Photo $photo
    ) {}

    public function handle(): void
    {
        try {
            // Télécharger temporairement l'image depuis S3
            $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
            file_put_contents($tempPath, file_get_contents($this->photo->original_url));

            $exif = @exif_read_data($tempPath);

            if ($exif) {
                $this->photo->update([
                    'camera' => $exif['Model'] ?? null,
                    'lens' => $exif['LensModel'] ?? null,
                    'iso' => $exif['ISOSpeedRatings'] ?? null,
                    'aperture' => isset($exif['FNumber']) ? 'f/' . $exif['FNumber'] : null,
                    'shutter_speed' => $exif['ExposureTime'] ?? null,
                    'focal_length' => isset($exif['FocalLength']) ? (int) $exif['FocalLength'] : null,
                    'taken_at' => isset($exif['DateTimeOriginal']) ?
                        \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']) : null,
                ]);
            }

            // Nettoyer
            unlink($tempPath);

        } catch (\Exception $e) {
            \Log::error('Erreur extraction EXIF: ' . $e->getMessage());
        }
    }
}
