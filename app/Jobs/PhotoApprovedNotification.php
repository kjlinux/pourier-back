<?php

namespace App\Jobs;

use App\Models\Photo;
use App\Notifications\PhotoApproved;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PhotoApprovedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Photo $photo
    ) {}

    public function handle(): void
    {
        try {
            if ($this->photo->photographer) {
                $this->photo->photographer->notify(new PhotoApproved($this->photo));

                Log::info('Photo approved notification sent', [
                    'photo_id' => $this->photo->id,
                    'photographer_id' => $this->photo->photographer_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send photo approved notification', [
                'photo_id' => $this->photo->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PhotoApprovedNotification job failed permanently', [
            'photo_id' => $this->photo->id,
            'error' => $exception->getMessage()
        ]);
    }
}
