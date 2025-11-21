<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PhotographerProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PhotographerProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $photographers = User::where('account_type', 'photographer')->get();
        $admin = User::where('account_type', 'admin')->first();

        $burkinabeLocations = [
            'Ouagadougou, Burkina Faso',
            'Bobo-Dioulasso, Burkina Faso',
            'Koudougou, Burkina Faso',
            'Banfora, Burkina Faso',
            'Ouahigouya, Burkina Faso',
            'Dédougou, Burkina Faso',
            'Kaya, Burkina Faso',
            'Fada N\'gourma, Burkina Faso',
            'Tenkodogo, Burkina Faso',
            'Gaoua, Burkina Faso',
        ];

        $specialtiesOptions = [
            ['portrait', 'culture africaine', 'événements'],
            ['paysage', 'nature', 'wildlife'],
            ['événements', 'mariage', 'célébrations'],
            ['nature', 'wildlife', 'conservation'],
            ['architecture', 'urbain', 'street photography'],
            ['street photography', 'lifestyle', 'portrait'],
            ['mode', 'lifestyle', 'portrait'],
            ['paysage', 'voyage', 'nature'],
            ['portrait', 'studio'],
            ['culinaire', 'culture africaine', 'lifestyle'],
        ];

        $profileData = [
            [
                'username' => 'abdoulaye_traore_photo',
                'display_name' => 'Abdoulaye Traoré Photography',
                'instagram' => 'abdoulaye.traore.photo',
                'portfolio_url' => 'https://abdoulayetraore.pouire.bf',
                'status' => 'approved',
                'total_sales' => rand(50, 150),
                'total_revenue' => rand(500000, 2000000),
                'followers_count' => rand(150, 500),
            ],
            [
                'username' => 'fatoumata_vision',
                'display_name' => 'Fatoumata Vision',
                'instagram' => 'fatoumata.vision',
                'portfolio_url' => 'https://fatoumatavision.pouire.bf',
                'status' => 'approved',
                'total_sales' => rand(40, 120),
                'total_revenue' => rand(400000, 1500000),
                'followers_count' => rand(200, 600),
            ],
            [
                'username' => 'moussa_events',
                'display_name' => 'Moussa Events Photography',
                'instagram' => 'moussa.events.bf',
                'website' => 'https://moussaevents.bf',
                'status' => 'approved',
                'total_sales' => rand(60, 180),
                'total_revenue' => rand(800000, 2500000),
                'followers_count' => rand(300, 800),
            ],
            [
                'username' => 'mariama_nature',
                'display_name' => 'Mariama Nature Photography',
                'instagram' => 'mariama.nature',
                'portfolio_url' => 'https://mariamanature.pouire.bf',
                'status' => 'approved',
                'total_sales' => rand(30, 90),
                'total_revenue' => rand(350000, 1200000),
                'followers_count' => rand(100, 400),
            ],
            [
                'username' => 'seydou_archi',
                'display_name' => 'Seydou Architecture & Urban',
                'instagram' => 'seydou.archi',
                'website' => 'https://seydouarchitecture.bf',
                'status' => 'approved',
                'total_sales' => rand(25, 80),
                'total_revenue' => rand(300000, 1000000),
                'followers_count' => rand(120, 350),
            ],
            [
                'username' => 'assetou_street',
                'display_name' => 'Assétou Street Photography',
                'instagram' => 'assetou.street',
                'portfolio_url' => 'https://assetoustreet.pouire.bf',
                'status' => 'approved',
                'total_sales' => rand(45, 130),
                'total_revenue' => rand(450000, 1600000),
                'followers_count' => rand(250, 700),
            ],
            [
                'username' => 'ibrahim_lifestyle',
                'display_name' => 'Ibrahim Lifestyle & Fashion',
                'instagram' => 'ibrahim.lifestyle.bf',
                'website' => 'https://ibrahimlifestyle.bf',
                'status' => 'approved',
                'total_sales' => rand(70, 200),
                'total_revenue' => rand(900000, 3000000),
                'followers_count' => rand(400, 1200),
            ],
            [
                'username' => 'awa_landscapes',
                'display_name' => 'Awa Landscapes',
                'instagram' => 'awa.landscapes',
                'portfolio_url' => 'https://awalandscapes.pouire.bf',
                'status' => 'approved',
                'total_sales' => rand(35, 100),
                'total_revenue' => rand(400000, 1300000),
                'followers_count' => rand(180, 500),
            ],
            [
                'username' => 'ousmane_portraits',
                'display_name' => 'Ousmane Portraits',
                'instagram' => 'ousmane.portraits',
                'status' => 'pending',
                'total_sales' => 0,
                'total_revenue' => 0,
                'followers_count' => rand(10, 50),
            ],
            [
                'username' => 'ramatou_food_culture',
                'display_name' => 'Ramatou Food & Culture',
                'instagram' => 'ramatou.foodculture',
                'status' => 'rejected',
                'rejection_reason' => 'Portfolio incomplet. Veuillez soumettre au moins 10 photos de haute qualité avant de pouvoir être approuvé.',
                'total_sales' => 0,
                'total_revenue' => 0,
                'followers_count' => rand(5, 30),
            ],
        ];

        foreach ($photographers as $index => $photographer) {
            $data = $profileData[$index];
            $isApproved = $data['status'] === 'approved';

            PhotographerProfile::create([
                'id' => Str::uuid(),
                'user_id' => $photographer->id,
                'username' => $data['username'],
                'display_name' => $data['display_name'],
                'cover_photo_url' => 'https://picsum.photos/seed/' . $data['username'] . '/1920/400',
                'location' => $burkinabeLocations[$index],
                'website' => $data['website'] ?? null,
                'instagram' => $data['instagram'],
                'portfolio_url' => $data['portfolio_url'] ?? null,
                'specialties' => json_encode($specialtiesOptions[$index]),
                'status' => $data['status'],
                'commission_rate' => 80.00,
                'total_sales' => $data['total_sales'],
                'total_revenue' => $data['total_revenue'],
                'followers_count' => $data['followers_count'],
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'approved_by' => $isApproved ? $admin->id : null,
                'approved_at' => $isApproved ? now()->subDays(rand(30, 180)) : null,
            ]);
        }

        $this->command->info('✅ Photographer profiles seeded: 8 approved, 1 pending, 1 rejected');
    }
}
