<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌍 Seeding Pouire database...');
        $this->command->newLine();

        // 1. Seed Roles & Permissions
        $this->call(RolePermissionSeeder::class);

        // 2. Seed Categories
        $this->call(CategorySeeder::class);

        // 3. Seed Admin User
        $this->call(UserSeeder::class);

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
    }
}
