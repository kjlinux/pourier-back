<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Photo;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PhotoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $approvedPhotographers = User::whereHas('photographerProfile', function ($query) {
            $query->where('status', 'approved');
        })->get();

        $categories = Category::all();
        $moderator = User::role('moderator')->first();

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
            'Parc National d\'Arly, Burkina Faso',
            'Cascades de Karfiguéla, Banfora',
            'Pics de Sindou, Burkina Faso',
            'Lac Tengrela, Banfora',
            'Dômes de Fabédougou, Banfora',
            'Mare aux hippopotames, Bala',
            'Réserve de Nazinga, Burkina Faso',
        ];

        $cameras = ['Canon EOS R5', 'Nikon Z7 II', 'Sony A7R IV', 'Canon EOS 5D Mark IV', 'Nikon D850', 'Sony A7 III', 'Fujifilm X-T4'];
        $lenses = ['24-70mm f/2.8', '70-200mm f/2.8', '50mm f/1.4', '85mm f/1.8', '16-35mm f/4', '100-400mm f/5.6', '35mm f/1.8'];

        $photoTitles = [
            // Portraits
            ['Femme Peul en tenue traditionnelle', 'Portrait d\'un artisan forgeron', 'Enfant souriant au marché central', 'Vendeuse de fruits au marché'],
            // Paysages
            ['Coucher de soleil sur les Pics de Sindou', 'Cascades de Karfiguéla au crépuscule', 'Paysage aride du Sahel burkinabé', 'Dômes de Fabédougou'],
            // Nature
            ['Hippopotames dans la mare de Bala', 'Baobab centenaire', 'Oiseaux migrateurs sur le lac Tengrela', 'Faune sauvage au Parc d\'Arly'],
            // Événements
            ['Danse traditionnelle lors du FESPACO', 'Cérémonie de mariage mossi', 'Festival de masques au village', 'Concert de reggae à Ouagadougou'],
            // Street
            ['Marché central de Ouagadougou en pleine activité', 'Circulation de motos-taxis', 'Vendeur ambulant de pains', 'Rue animée de Bobo-Dioulasso'],
            // Architecture
            ['Grande Mosquée de Bobo-Dioulasso', 'Architecture traditionnelle en banco', 'Bâtiment moderne du centre-ville', 'Palais de Mogho Naba'],
            // Lifestyle
            ['Famille burkinabé partageant un repas', 'Jeunes jouant au football', 'Artiste peintre dans son atelier', 'Mode africaine contemporaine'],
            // Culture
            ['Masque traditionnel Bobo', 'Tissage traditionnel du Faso Dan Fani', 'Poterie artisanale', 'Instruments de musique traditionnels'],
        ];

        $descriptions = [
            'Capturée lors d\'une journée ensoleillée au cœur du Burkina Faso, cette image représente l\'authenticité et la beauté de notre patrimoine culturel.',
            'Une scène de vie quotidienne qui illustre la richesse des traditions burkinabè et l\'hospitalité légendaire de notre peuple.',
            'Photographie prise lors du FESPACO, célébrant la créativité et l\'art cinématographique africain.',
            'Image capturée au lever du soleil, mettant en valeur les paysages époustouflants du Burkina Faso.',
            'Cette photo témoigne de la biodiversité exceptionnelle que nous protégeons dans nos réserves naturelles.',
            'Moment capturé spontanément dans les rues animées de nos villes, reflétant l\'énergie et la vitalité burkinabè.',
            'Architecture unique qui fusionne traditions ancestrales et modernité, symbole du Burkina Faso contemporain.',
            'Célébration de la mode et du design africains, mettant en avant le talent de nos créateurs locaux.',
        ];

        $frenchTags = [
            ['burkina faso', 'portrait', 'culture', 'tradition', 'peul'],
            ['paysage', 'nature', 'coucher de soleil', 'pics de sindou', 'banfora'],
            ['faune', 'wildlife', 'hippopotame', 'conservation', 'nature'],
            ['événement', 'fespaco', 'festival', 'culture', 'célébration'],
            ['street', 'urbain', 'vie quotidienne', 'ouagadougou', 'marché'],
            ['architecture', 'mosquée', 'banco', 'bobo-dioulasso', 'patrimoine'],
            ['lifestyle', 'mode', 'afrique', 'contemporain', 'créateur'],
            ['artisanat', 'tradition', 'culture africaine', 'masque', 'art'],
        ];

        $colorPalettes = [
            ['#8B4513', '#D2691E', '#F4A460', '#FFA500', '#FFD700'],
            ['#4169E1', '#87CEEB', '#FFD700', '#FF8C00', '#FF6347'],
            ['#228B22', '#32CD32', '#8B4513', '#A0522D', '#D2691E'],
            ['#FF6347', '#FF4500', '#FFD700', '#FFA500', '#8B4513'],
            ['#696969', '#808080', '#A9A9A9', '#C0C0C0', '#D3D3D3'],
        ];

        $totalPhotos = 0;
        $approvedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;

        foreach ($approvedPhotographers as $photographer) {
            $photosCount = rand(10, 20);

            for ($i = 0; $i < $photosCount; $i++) {
                $category = $categories->random();
                $categoryIndex = min($category->display_order - 1, 7);
                $titleSet = $photoTitles[$categoryIndex] ?? $photoTitles[0];

                $uniqueSeed = $photographer->id . '-' . $i;
                $width = [1920, 2560, 3840][rand(0, 2)];
                $height = [1080, 1440, 2160][rand(0, 2)];

                // Statut: 90% approved, 5% pending, 5% rejected
                $statusRand = rand(1, 100);
                if ($statusRand <= 90) {
                    $status = 'approved';
                    $approvedCount++;
                } elseif ($statusRand <= 95) {
                    $status = 'pending';
                    $pendingCount++;
                } else {
                    $status = 'rejected';
                    $rejectedCount++;
                }

                $isApproved = $status === 'approved';
                $isFeatured = $isApproved && rand(1, 100) <= 10; // 10% featured

                $standardPrice = rand(1, 20) * 500; // 500 à 10,000 FCFA
                $extendedPrice = $standardPrice * 2;

                Photo::create([
                    'id' => Str::uuid(),
                    'photographer_id' => $photographer->id,
                    'category_id' => $category->id,
                    'title' => $titleSet[array_rand($titleSet)] . ' ' . rand(1, 999),
                    'description' => $descriptions[array_rand($descriptions)],
                    'tags' => json_encode($frenchTags[array_rand($frenchTags)]),
                    'original_url' => "https://picsum.photos/seed/{$uniqueSeed}/{$width}/{$height}",
                    'preview_url' => "https://picsum.photos/seed/{$uniqueSeed}-preview/{$width}/{$height}",
                    'thumbnail_url' => "https://picsum.photos/seed/{$uniqueSeed}/400/300",
                    'width' => $width,
                    'height' => $height,
                    'file_size' => rand(2000000, 8000000),
                    'format' => 'jpg',
                    'color_palette' => json_encode($colorPalettes[array_rand($colorPalettes)]),
                    'camera' => $cameras[array_rand($cameras)],
                    'lens' => $lenses[array_rand($lenses)],
                    'iso' => [100, 200, 400, 800, 1600][rand(0, 4)],
                    'aperture' => ['f/1.4', 'f/1.8', 'f/2.8', 'f/4', 'f/5.6'][rand(0, 4)],
                    'shutter_speed' => ['1/125', '1/250', '1/500', '1/1000', '1/2000'][rand(0, 4)],
                    'focal_length' => rand(24, 200) . 'mm',
                    'taken_at' => now()->subDays(rand(1, 365)),
                    'location' => $burkinabeLocations[array_rand($burkinabeLocations)],
                    'price_standard' => $standardPrice,
                    'price_extended' => $extendedPrice,
                    'views_count' => $isApproved ? rand(10, 500) : 0,
                    'downloads_count' => $isApproved ? rand(0, 50) : 0,
                    'favorites_count' => $isApproved ? rand(0, 30) : 0,
                    'sales_count' => $isApproved ? rand(0, 20) : 0,
                    'is_public' => true,
                    'status' => $status,
                    'rejection_reason' => $status === 'rejected' ? 'Qualité d\'image insuffisante. Veuillez soumettre une photo de meilleure résolution.' : null,
                    'moderated_by' => $status !== 'pending' ? $moderator->id : null,
                    'moderated_at' => $status !== 'pending' ? now()->subDays(rand(1, 30)) : null,
                    'featured' => $isFeatured,
                    'featured_until' => $isFeatured ? now()->addDays(rand(7, 30)) : null,
                ]);

                $totalPhotos++;
            }
        }

        $this->command->info("✅ Photos seeded: {$totalPhotos} total ({$approvedCount} approved, {$pendingCount} pending, {$rejectedCount} rejected)");
    }
}
