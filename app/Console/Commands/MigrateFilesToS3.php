<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Photo;
use App\Models\PhotographerProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateFilesToS3 extends Command
{
    protected $signature = 'storage:migrate-to-s3
                            {--dry-run : Run without making changes}
                            {--batch-size=50 : Number of files to process per batch}
                            {--type= : Migrate specific type only (photos|avatars|covers|invoices|all)}';

    protected $description = 'Migrate files from local storage to AWS S3';

    private int $successCount = 0;

    private int $failureCount = 0;

    private int $skippedCount = 0;

    private array $errors = [];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $type = $this->option('type') ?: 'all';

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting migration from local storage to S3...');
        $this->newLine();

        // Check S3 connectivity
        if (! $this->testS3Connection()) {
            $this->error('Failed to connect to S3. Please check your credentials.');

            return 1;
        }

        // Migrate based on type
        if ($type === 'all' || $type === 'photos') {
            $this->migratePhotos($dryRun, $batchSize);
        }

        if ($type === 'all' || $type === 'avatars') {
            $this->migrateAvatars($dryRun, $batchSize);
        }

        if ($type === 'all' || $type === 'covers') {
            $this->migrateCovers($dryRun, $batchSize);
        }

        if ($type === 'all' || $type === 'invoices') {
            $this->migrateInvoices($dryRun, $batchSize);
        }

        // Summary
        $this->newLine();
        $this->info('Migration Summary:');
        $this->info("✓ Successful: {$this->successCount}");
        $this->warn("⊘ Skipped: {$this->skippedCount}");
        $this->error("✗ Failed: {$this->failureCount}");

        if (! empty($this->errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($this->errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return $this->failureCount > 0 ? 1 : 0;
    }

    private function testS3Connection(): bool
    {
        try {
            Storage::disk('s3')->exists('test-connection-'.time());

            return true;
        } catch (\Exception $e) {
            $this->error('S3 Connection Error: '.$e->getMessage());

            return false;
        }
    }

    private function migratePhotos(bool $dryRun, int $batchSize): void
    {
        $this->info('Migrating photos...');

        $photos = Photo::whereNotNull('original_url')
            ->orWhereNotNull('preview_url')
            ->orWhereNotNull('thumbnail_url')
            ->get();

        $bar = $this->output->createProgressBar($photos->count());
        $bar->start();

        foreach ($photos as $photo) {
            DB::beginTransaction();
            try {
                $updated = false;

                // Migrate original
                if ($photo->original_url && ! $this->isS3Url($photo->original_url)) {
                    $newUrl = $this->migrateFile(
                        $photo->original_url,
                        'private',
                        $dryRun
                    );
                    if ($newUrl && ! $dryRun) {
                        $photo->original_url = $newUrl;
                        $updated = true;
                    }
                }

                // Migrate preview
                if ($photo->preview_url && ! $this->isS3Url($photo->preview_url)) {
                    $newUrl = $this->migrateFile(
                        $photo->preview_url,
                        'public',
                        $dryRun
                    );
                    if ($newUrl && ! $dryRun) {
                        $photo->preview_url = $newUrl;
                        $updated = true;
                    }
                }

                // Migrate thumbnail
                if ($photo->thumbnail_url && ! $this->isS3Url($photo->thumbnail_url)) {
                    $newUrl = $this->migrateFile(
                        $photo->thumbnail_url,
                        'public',
                        $dryRun
                    );
                    if ($newUrl && ! $dryRun) {
                        $photo->thumbnail_url = $newUrl;
                        $updated = true;
                    }
                }

                if ($updated && ! $dryRun) {
                    $photo->save();
                }

                DB::commit();
                $this->successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->failureCount++;
                $this->errors[] = "Photo {$photo->id}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateAvatars(bool $dryRun, int $batchSize): void
    {
        $this->info('Migrating avatars...');

        $users = User::whereNotNull('avatar_url')->get();
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            try {
                if (! $this->isS3Url($user->avatar_url)) {
                    $newUrl = $this->migrateFile($user->avatar_url, 'public', $dryRun);
                    if ($newUrl && ! $dryRun) {
                        $user->avatar_url = $newUrl;
                        $user->save();
                    }
                    $this->successCount++;
                } else {
                    $this->skippedCount++;
                }
            } catch (\Exception $e) {
                $this->failureCount++;
                $this->errors[] = "Avatar for user {$user->id}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateCovers(bool $dryRun, int $batchSize): void
    {
        $this->info('Migrating cover photos...');

        $profiles = PhotographerProfile::whereNotNull('cover_photo_url')->get();
        $bar = $this->output->createProgressBar($profiles->count());
        $bar->start();

        foreach ($profiles as $profile) {
            try {
                if (! $this->isS3Url($profile->cover_photo_url)) {
                    $newUrl = $this->migrateFile($profile->cover_photo_url, 'public', $dryRun);
                    if ($newUrl && ! $dryRun) {
                        $profile->cover_photo_url = $newUrl;
                        $profile->save();
                    }
                    $this->successCount++;
                } else {
                    $this->skippedCount++;
                }
            } catch (\Exception $e) {
                $this->failureCount++;
                $this->errors[] = "Cover for profile {$profile->id}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateInvoices(bool $dryRun, int $batchSize): void
    {
        $this->info('Migrating invoices...');

        $orders = Order::whereNotNull('invoice_path')->get();
        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            try {
                if (! $this->isS3Url($order->invoice_path)) {
                    $newUrl = $this->migrateFile($order->invoice_path, 'private', $dryRun);
                    if ($newUrl && ! $dryRun) {
                        $order->invoice_path = $newUrl;
                        $order->save();
                    }
                    $this->successCount++;
                } else {
                    $this->skippedCount++;
                }
            } catch (\Exception $e) {
                $this->failureCount++;
                $this->errors[] = "Invoice for order {$order->id}: {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateFile(string $localPath, string $visibility, bool $dryRun): ?string
    {
        // Extract path from URL if needed
        if (filter_var($localPath, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($localPath);
            $localPath = ltrim($parsed['path'], '/');
        }

        // Check if file exists locally
        if (! Storage::disk('local')->exists($localPath)) {
            $this->skippedCount++;

            return null;
        }

        if ($dryRun) {
            $this->line("  Would migrate: {$localPath}");

            return "s3://simulated/{$localPath}";
        }

        // Get file contents
        $contents = Storage::disk('local')->get($localPath);

        // Determine content type
        $extension = pathinfo($localPath, PATHINFO_EXTENSION);
        $mimeType = $this->getMimeType($extension);

        // Upload to S3
        $options = [
            'visibility' => $visibility,
            'CacheControl' => $visibility === 'public' ? 'max-age=31536000' : 'max-age=0',
            'ContentType' => $mimeType,
        ];

        Storage::disk('s3')->put($localPath, $contents, $options);

        // Return S3 URL
        return Storage::disk('s3')->url($localPath);
    }

    private function isS3Url(string $url): bool
    {
        return str_contains($url, 's3.amazonaws.com') ||
               str_contains($url, env('AWS_BUCKET'));
    }

    private function getMimeType(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}
