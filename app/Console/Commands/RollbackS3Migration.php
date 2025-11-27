<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Photo;
use App\Models\User;
use App\Models\PhotographerProfile;
use App\Models\Order;

class RollbackS3Migration extends Command
{
    protected $signature = 'storage:rollback-s3-migration
                            {--dry-run : Preview changes without applying}';

    protected $description = 'Rollback S3 URLs to local paths (emergency rollback)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        } elseif (!$this->confirm('This will revert all S3 URLs back to local paths. Continue?')) {
            return 0;
        }

        $this->info('Rolling back S3 migration...');

        // Rollback photos
        $this->info('Rolling back photos...');
        $photos = Photo::where('original_url', 'like', '%s3.amazonaws.com%')
            ->orWhere('preview_url', 'like', '%s3.amazonaws.com%')
            ->orWhere('thumbnail_url', 'like', '%s3.amazonaws.com%')
            ->get();

        $bar = $this->output->createProgressBar($photos->count());
        $bar->start();

        foreach ($photos as $photo) {
            if (!$dryRun) {
                $photo->update([
                    'original_url' => $this->convertS3ToLocal($photo->original_url),
                    'preview_url' => $this->convertS3ToLocal($photo->preview_url),
                    'thumbnail_url' => $this->convertS3ToLocal($photo->thumbnail_url),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Rollback avatars
        $this->info('Rolling back avatars...');
        $users = User::where('avatar_url', 'like', '%s3.amazonaws.com%')->get();
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            if (!$dryRun) {
                $user->update([
                    'avatar_url' => $this->convertS3ToLocal($user->avatar_url),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Rollback covers
        $this->info('Rolling back cover photos...');
        $profiles = PhotographerProfile::where('cover_photo_url', 'like', '%s3.amazonaws.com%')->get();
        $bar = $this->output->createProgressBar($profiles->count());
        $bar->start();

        foreach ($profiles as $profile) {
            if (!$dryRun) {
                $profile->update([
                    'cover_photo_url' => $this->convertS3ToLocal($profile->cover_photo_url),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Rollback invoices
        $this->info('Rolling back invoices...');
        $orders = Order::where('invoice_path', 'like', '%s3.amazonaws.com%')->get();
        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            if (!$dryRun) {
                $order->update([
                    'invoice_path' => $this->convertS3ToLocal($order->invoice_path),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Rollback complete!');
        if (!$dryRun) {
            $this->warn('Remember to update .env: FILESYSTEM_DISK=local');
        }

        return 0;
    }

    private function convertS3ToLocal(?string $s3Url): ?string
    {
        if (!$s3Url || !str_contains($s3Url, 's3.amazonaws.com')) {
            return $s3Url;
        }

        // Extract path from S3 URL
        $parsed = parse_url($s3Url);
        $path = ltrim($parsed['path'], '/');

        // Remove bucket name if present
        $bucket = env('AWS_BUCKET');
        if ($bucket && str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }

        return $path;
    }
}
