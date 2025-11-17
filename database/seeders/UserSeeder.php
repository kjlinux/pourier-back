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
        // Burkinabé first names
        $maleFirstNames = ['Abdoulaye', 'Boureima', 'Ibrahim', 'Moussa', 'Seydou', 'Ousmane', 'Souleymane', 'Yacouba', 'Adama', 'Issouf'];
        $femaleFirstNames = ['Fatimata', 'Aminata', 'Mariama', 'Fatoumata', 'Assétou', 'Awa', 'Ramatou', 'Safiatou', 'Zénabou', 'Salimata'];
        $lastNames = ['Traoré', 'Ouédraogo', 'Kaboré', 'Sawadogo', 'Compaoré', 'Zongo', 'Ouattara', 'Sankara', 'Dipama', 'Nikiema'];

        // 1. Create Admin
        $admin = User::create([
            'id' => Str::uuid(),
            'email' => 'admin@pourier.bf',
            'password' => Hash::make('password'),
            'first_name' => 'Mohamed',
            'last_name' => 'Kaboré',
            'avatar_url' => 'https://i.pravatar.cc/300?img=1',
            'phone' => '+226 70 12 34 56',
            'bio' => 'Administrateur de la plateforme Pourier',
            'account_type' => 'admin',
            'is_verified' => true,
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login' => now()->subHours(2),
        ]);
        $admin->assignRole('admin');

        // 2. Create Moderators
        $moderatorData = [
            [
                'email' => 'moderator1@pourier.bf',
                'first_name' => 'Aminata',
                'last_name' => 'Sawadogo',
                'phone' => '+226 71 23 45 67',
                'img' => 2,
            ],
            [
                'email' => 'moderator2@pourier.bf',
                'first_name' => 'Boureima',
                'last_name' => 'Zongo',
                'phone' => '+226 72 34 56 78',
                'img' => 3,
            ],
        ];

        foreach ($moderatorData as $index => $data) {
            $moderator = User::create([
                'id' => Str::uuid(),
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'avatar_url' => "https://i.pravatar.cc/300?img={$data['img']}",
                'phone' => $data['phone'],
                'bio' => 'Modérateur de contenu sur Pourier',
                'account_type' => 'admin',
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login' => now()->subHours(rand(1, 48)),
            ]);
            $moderator->assignRole('moderator');
        }

        // 3. Create Photographers (10)
        $photographerData = [
            [
                'first_name' => 'Abdoulaye',
                'last_name' => 'Traoré',
                'email' => 'abdoulaye.traore@pourier.bf',
                'phone' => '+226 70 11 22 33',
                'bio' => 'Photographe professionnel spécialisé dans les portraits et la photographie culturelle africaine. Passionné par la capture de l\'authenticité burkinabè.',
                'img' => 10,
            ],
            [
                'first_name' => 'Fatoumata',
                'last_name' => 'Ouédraogo',
                'email' => 'fatoumata.ouedraogo@pourier.bf',
                'phone' => '+226 71 22 33 44',
                'bio' => 'Artiste visuelle et photographe documentaire. Je capture la beauté des paysages et de la vie quotidienne au Burkina Faso.',
                'img' => 11,
            ],
            [
                'first_name' => 'Moussa',
                'last_name' => 'Compaoré',
                'email' => 'moussa.compaore@pourier.bf',
                'phone' => '+226 72 33 44 55',
                'bio' => 'Photographe d\'événements basé à Ouagadougou. Spécialiste des mariages, baptêmes et célébrations traditionnelles.',
                'img' => 12,
            ],
            [
                'first_name' => 'Mariama',
                'last_name' => 'Sankara',
                'email' => 'mariama.sankara@pourier.bf',
                'phone' => '+226 73 44 55 66',
                'bio' => 'Photographe de nature et de wildlife. J\'explore les parcs nationaux et réserves du Burkina pour capturer sa biodiversité.',
                'img' => 13,
            ],
            [
                'first_name' => 'Seydou',
                'last_name' => 'Ouattara',
                'email' => 'seydou.ouattara@pourier.bf',
                'phone' => '+226 74 55 66 77',
                'bio' => 'Photographe d\'architecture et urbain. Je documente l\'évolution des villes burkinabè et leur architecture unique.',
                'img' => 14,
            ],
            [
                'first_name' => 'Assétou',
                'last_name' => 'Dipama',
                'email' => 'assetou.dipama@pourier.bf',
                'phone' => '+226 75 66 77 88',
                'bio' => 'Street photographer passionnée. Mon objectif: capturer l\'essence de la vie dans les rues de Bobo-Dioulasso.',
                'img' => 15,
            ],
            [
                'first_name' => 'Ibrahim',
                'last_name' => 'Nikiema',
                'email' => 'ibrahim.nikiema@pourier.bf',
                'phone' => '+226 76 77 88 99',
                'bio' => 'Photographe lifestyle et mode. Je collabore avec des créateurs locaux pour promouvoir la mode africaine contemporaine.',
                'img' => 16,
            ],
            [
                'first_name' => 'Awa',
                'last_name' => 'Kaboré',
                'email' => 'awa.kabore@pourier.bf',
                'phone' => '+226 77 88 99 00',
                'bio' => 'Photographe de paysages et de voyages. J\'ai parcouru tout le Burkina Faso pour immortaliser ses merveilles naturelles.',
                'img' => 17,
            ],
            [
                'first_name' => 'Ousmane',
                'last_name' => 'Zongo',
                'email' => 'ousmane.zongo@pourier.bf',
                'phone' => '+226 78 99 00 11',
                'bio' => 'Nouveau photographe passionné par la photographie de portrait. En attente d\'approbation.',
                'img' => 18,
            ],
            [
                'first_name' => 'Ramatou',
                'last_name' => 'Sawadogo',
                'email' => 'ramatou.sawadogo@pourier.bf',
                'phone' => '+226 79 00 11 22',
                'bio' => 'Photographe émergente spécialisée dans la photographie culinaire et culturelle.',
                'img' => 19,
            ],
        ];

        foreach ($photographerData as $data) {
            $photographer = User::create([
                'id' => Str::uuid(),
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'avatar_url' => "https://i.pravatar.cc/300?img={$data['img']}",
                'phone' => $data['phone'],
                'bio' => $data['bio'],
                'account_type' => 'photographer',
                'is_verified' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login' => now()->subHours(rand(1, 72)),
            ]);
            $photographer->assignRole('photographer');
        }

        // 4. Create Buyers (20)
        for ($i = 0; $i < 20; $i++) {
            $isMale = rand(0, 1);
            $firstName = $isMale ? $maleFirstNames[array_rand($maleFirstNames)] : $femaleFirstNames[array_rand($femaleFirstNames)];
            $lastName = $lastNames[array_rand($lastNames)];

            $buyer = User::create([
                'id' => Str::uuid(),
                'email' => strtolower($firstName . '.' . $lastName . $i . '@example.bf'),
                'password' => Hash::make('password'),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'avatar_url' => 'https://i.pravatar.cc/300?img=' . (20 + $i),
                'phone' => '+226 ' . rand(70, 79) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'bio' => rand(0, 1) ? 'Amateur de photographie et d\'art visuel.' : null,
                'account_type' => 'buyer',
                'is_verified' => rand(0, 10) > 2, // 80% verified
                'is_active' => true,
                'email_verified_at' => rand(0, 10) > 2 ? now()->subDays(rand(1, 60)) : null,
                'last_login' => rand(0, 10) > 3 ? now()->subHours(rand(1, 168)) : null,
            ]);
            $buyer->assignRole('buyer');
        }

        $this->command->info('✅ Users seeded successfully: 1 admin, 2 moderators, 10 photographers, 20 buyers');
    }
}
