<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Portrait',
                'slug' => 'portrait',
                'description' => 'Photos de portraits individuels et de groupes',
                'icon_url' => null,
                'parent_id' => null,
                'display_order' => 1,
                'is_active' => true,
                'photo_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Paysage',
                'slug' => 'paysage',
                'description' => 'Paysages naturels et urbains africains',
                'icon_url' => null,
                'parent_id' => null,
                'display_order' => 2,
                'is_active' => true,
                'photo_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Nature',
                'slug' => 'nature',
                'description' => 'Faune, flore et environnement naturel',
                'icon_url' => null,
                'parent_id' => null,
                'display_order' => 3,
                'is_active' => true,
                'photo_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Événements',
                'slug' => 'evenements',
                'description' => 'Mariages, cérémonies et événements culturels',
                'icon_url' => null,
                'parent_id' => null,
                'display_order' => 4,
                'is_active' => true,
                'photo_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Street Photography',
                'slug' => 'street-photography',
                'description' => 'Photographie de rue et scènes de vie quotidienne',
                'icon_url' => null,
                'parent_id' => null,
                'display_order' => 5,
                'is_active' => true,
                'photo_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Architecture',
                'slug' => 'architecture',
                'description' => 'Bâtiments, monuments et structures architecturales',
                'icon_url' => null,
                'parent_id' => null,
                'display_order' => 6,
                'is_active' => true,
                'photo_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'description' => 'Mode de vie, fashion et style africain',
                'icon_url' => null,
                'parent_id' => null,
                'display_order' => 7,
                'is_active' => true,
                'photo_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Culture Africaine',
                'slug' => 'culture-africaine',
                'description' => 'Traditions, artisanat et patrimoine culturel',
                'icon_url' => null,
                'parent_id' => null,
                'display_order' => 8,
                'is_active' => true,
                'photo_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('categories')->insert($categories);

        $this->command->info('8 categories created successfully!');
    }
}
