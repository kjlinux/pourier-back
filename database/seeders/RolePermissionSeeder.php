<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // Photo Management
            'upload-photos',
            'edit-own-photos',
            'delete-own-photos',
            'view-all-photos',
            'moderate-photos',
            'approve-photos',
            'reject-photos',
            'feature-photos',
            'delete-any-photo',

            // Revenue & Withdrawals
            'view-own-revenue',
            'view-all-revenue',
            'request-withdrawals',
            'approve-withdrawals',
            'reject-withdrawals',
            'complete-withdrawals',

            // User Management
            'view-users',
            'edit-users',
            'suspend-users',
            'activate-users',
            'delete-users',

            // Photographer Management
            'view-photographers',
            'approve-photographers',
            'reject-photographers',
            'suspend-photographers',
            'activate-photographers',

            // Analytics
            'view-own-analytics',
            'view-platform-analytics',

            // Orders
            'view-own-orders',
            'view-all-orders',
            'manage-orders',

            // Categories
            'manage-categories',

            // System
            'manage-featured-content',
            'view-dashboard',
        ];

        // Create permissions (skip if already exists)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api']
            );
        }

        // Create roles and assign permissions

        // Buyer Role
        $buyerRole = Role::firstOrCreate(['name' => 'buyer', 'guard_name' => 'api']);
        $buyerRole->syncPermissions([
            'view-own-orders',
        ]);

        // Photographer Role
        $photographerRole = Role::firstOrCreate(['name' => 'photographer', 'guard_name' => 'api']);
        $photographerRole->syncPermissions([
            'upload-photos',
            'edit-own-photos',
            'delete-own-photos',
            'view-own-revenue',
            'request-withdrawals',
            'view-own-analytics',
            'view-own-orders',
        ]);

        // Moderator Role (new - for delegated moderation)
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'api']);
        $moderatorRole->syncPermissions([
            'view-all-photos',
            'moderate-photos',
            'approve-photos',
            'reject-photos',
            'view-photographers',
            'approve-photographers',
            'reject-photographers',
            'view-dashboard',
        ]);

        // Admin Role (all permissions)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->syncPermissions(Permission::all());

        $this->command->info('✅ Roles and permissions seeded successfully!');
        $this->command->info('   Roles: buyer, photographer, moderator, admin');
        $this->command->info('   Permissions: ' . count($permissions) . ' total');
    }
}
