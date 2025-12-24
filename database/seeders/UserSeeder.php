<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'id' => Str::uuid(),
            'email' => 'admin@pouire.bf',
            'password' => Hash::make('password'),
            'first_name' => 'Admin',
            'last_name' => 'Pouire',
            'account_type' => 'admin',
            'is_verified' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $this->command->info('✅ Admin user created: admin@pouire.bf');
    }
}
