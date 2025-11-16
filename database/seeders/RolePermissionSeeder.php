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

        // Create permissions
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

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles and assign permissions

        // Buyer Role
        $buyerRole = Role::create(['name' => 'buyer', 'guard_name' => 'api']);
        $buyerRole->givePermissionTo([
            'view-own-orders',
        ]);

        // Photographer Role
        $photographerRole = Role::create(['name' => 'photographer', 'guard_name' => 'api']);
        $photographerRole->givePermissionTo([
            'upload-photos',
            'edit-own-photos',
            'delete-own-photos',
            'view-own-revenue',
            'request-withdrawals',
            'view-own-analytics',
            'view-own-orders',
        ]);

        // Moderator Role (new - for delegated moderation)
        $moderatorRole = Role::create(['name' => 'moderator', 'guard_name' => 'api']);
        $moderatorRole->givePermissionTo([
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
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(Permission::all());

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: buyer, photographer, moderator, admin');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
}
