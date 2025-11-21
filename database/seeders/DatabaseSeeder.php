<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with Burkinabé fictitious data.
     */
    public function run(): void
    {
        $this->command->info('🌍 Seeding Pouire database with Burkinabé data...');
        $this->command->newLine();

        // 1. Seed Roles & Permissions (if not already seeded)
        $this->call(RolePermissionSeeder::class);

        // 2. Seed Categories (if not already seeded)
        $this->call(CategorySeeder::class);

        // 3. Seed Users (Admin, Moderators, Photographers, Buyers)
        $this->call(UserSeeder::class);

        // 4. Seed Photographer Profiles
        $this->call(PhotographerProfileSeeder::class);

        // 5. Seed Photos with Lorem Picsum images
        $this->call(PhotoSeeder::class);

        // 6. Seed Orders
        $this->call(OrderSeeder::class);

        // 7. Seed Order Items
        $this->call(OrderItemSeeder::class);

        // 8. Seed Withdrawals
        $this->call(WithdrawalSeeder::class);

        // 9. Seed Revenues (monthly revenue tracking)
        $this->call(RevenueSeeder::class);

        // 10. Seed Favorites
        $this->call(FavoriteSeeder::class);

        // 11. Seed Follows
        $this->call(FollowSeeder::class);

        // 12. Seed Notifications
        $this->call(NotificationSeeder::class);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📝 Login credentials:');
        $this->command->info('   Admin: admin@pouire.bf / password');
        $this->command->info('   Moderator: moderator1@pouire.bf / password');
        $this->command->info('   Photographer: abdoulaye.traore@pouire.bf / password');
        $this->command->info('   Buyer: Use any buyer email / password');
        $this->command->newLine();
    }
}
