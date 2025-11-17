<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FollowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = User::where('account_type', 'buyer')->get();
        $photographers = User::whereHas('photographerProfile', function ($query) {
            $query->where('status', 'approved');
        })->get();

        if ($buyers->isEmpty() || $photographers->isEmpty()) {
            $this->command->warn('⚠️  No buyers or approved photographers found. Skipping FollowSeeder.');
            return;
        }

        $totalFollows = 0;

        // Buyers follow photographers
        foreach ($buyers as $buyer) {
            // Each buyer follows 1-5 photographers
            $followsCount = rand(1, 5);
            $selectedPhotographers = $photographers->random(min($followsCount, $photographers->count()));

            foreach ($selectedPhotographers as $photographer) {
                // Check if follow already exists
                $exists = DB::table('follows')
                    ->where('follower_id', $buyer->id)
                    ->where('following_id', $photographer->id)
                    ->exists();

                if (!$exists) {
                    DB::table('follows')->insert([
                        'id' => Str::uuid(),
                        'follower_id' => $buyer->id,
                        'following_id' => $photographer->id,
                        'created_at' => now()->subDays(rand(1, 90)),
                        'updated_at' => now()->subDays(rand(1, 90)),
                    ]);

                    $totalFollows++;
                }
            }
        }

        // Update followers_count on photographer profiles
        foreach ($photographers as $photographer) {
            $followersCount = DB::table('follows')
                ->where('following_id', $photographer->id)
                ->count();

            if ($photographer->photographerProfile) {
                $photographer->photographerProfile->update([
                    'followers_count' => $followersCount,
                ]);
            }
        }

        $this->command->info("✅ Follows seeded: {$totalFollows} total follow relationships");
    }
}
