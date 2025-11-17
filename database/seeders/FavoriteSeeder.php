<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Photo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = User::where('account_type', 'buyer')->get();
        $approvedPhotos = Photo::where('status', 'approved')->get();

        if ($buyers->isEmpty() || $approvedPhotos->isEmpty()) {
            $this->command->warn('⚠️  No buyers or approved photos found. Skipping FavoriteSeeder.');
            return;
        }

        $totalFavorites = 0;

        foreach ($buyers as $buyer) {
            // Each buyer favorites 2-10 photos randomly
            $favoritesCount = rand(2, 10);
            $selectedPhotos = $approvedPhotos->random(min($favoritesCount, $approvedPhotos->count()));

            foreach ($selectedPhotos as $photo) {
                // Check if favorite already exists (prevent duplicates)
                $exists = DB::table('favorites')
                    ->where('user_id', $buyer->id)
                    ->where('photo_id', $photo->id)
                    ->exists();

                if (!$exists) {
                    DB::table('favorites')->insert([
                        'id' => Str::uuid(),
                        'user_id' => $buyer->id,
                        'photo_id' => $photo->id,
                        'created_at' => now()->subDays(rand(1, 60)),
                        'updated_at' => now()->subDays(rand(1, 60)),
                    ]);

                    // Increment favorites_count on photo
                    $photo->increment('favorites_count');

                    $totalFavorites++;
                }
            }
        }

        $this->command->info("✅ Favorites seeded: {$totalFavorites} total favorites from {$buyers->count()} buyers");
    }
}
