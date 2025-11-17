<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use App\Models\Photo;
use App\Models\Order;
use App\Models\Withdrawal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $photographers = User::whereHas('photographerProfile')->get();
        $buyers = User::where('account_type', 'buyer')->get();

        if ($photographers->isEmpty() && $buyers->isEmpty()) {
            $this->command->warn('⚠️  No users found. Skipping NotificationSeeder.');
            return;
        }

        $totalNotifications = 0;

        // Notification templates for photographers
        $photographerNotifications = [
            [
                'type' => 'photo_approved',
                'title' => 'Photo approuvée',
                'message' => 'Votre photo "{photo_title}" a été approuvée et est maintenant visible sur la plateforme.',
            ],
            [
                'type' => 'photo_rejected',
                'title' => 'Photo rejetée',
                'message' => 'Votre photo "{photo_title}" a été rejetée. Raison: {reason}',
            ],
            [
                'type' => 'new_sale',
                'title' => 'Nouvelle vente !',
                'message' => 'Félicitations ! Votre photo "{photo_title}" a été vendue pour {amount} FCFA.',
            ],
            [
                'type' => 'withdrawal_completed',
                'title' => 'Retrait complété',
                'message' => 'Votre demande de retrait de {amount} FCFA a été traitée avec succès.',
            ],
            [
                'type' => 'withdrawal_rejected',
                'title' => 'Retrait rejeté',
                'message' => 'Votre demande de retrait de {amount} FCFA a été rejetée. Raison: {reason}',
            ],
            [
                'type' => 'new_follower',
                'title' => 'Nouveau follower',
                'message' => '{user_name} a commencé à vous suivre.',
            ],
            [
                'type' => 'monthly_report',
                'title' => 'Rapport mensuel disponible',
                'message' => 'Votre rapport de revenus pour le mois de {month} est maintenant disponible.',
            ],
        ];

        // Notification templates for buyers
        $buyerNotifications = [
            [
                'type' => 'order_completed',
                'title' => 'Commande complétée',
                'message' => 'Votre commande #{order_number} a été traitée avec succès. Montant: {amount} FCFA.',
            ],
            [
                'type' => 'order_failed',
                'title' => 'Échec de paiement',
                'message' => 'Le paiement de votre commande #{order_number} a échoué. Veuillez réessayer.',
            ],
            [
                'type' => 'download_ready',
                'title' => 'Téléchargements disponibles',
                'message' => 'Vos photos sont prêtes à être téléchargées. Lien valide pendant 30 jours.',
            ],
            [
                'type' => 'new_photo_from_followed',
                'title' => 'Nouvelle photo',
                'message' => '{photographer_name} a publié une nouvelle photo "{photo_title}".',
            ],
        ];

        // Create notifications for photographers
        foreach ($photographers as $photographer) {
            $notificationsCount = rand(3, 8);

            for ($i = 0; $i < $notificationsCount; $i++) {
                $template = $photographerNotifications[array_rand($photographerNotifications)];
                $createdAt = now()->subDays(rand(1, 60));
                $isRead = rand(0, 10) > 3; // 70% read

                // Customize message based on type
                $message = $template['message'];
                $data = ['type' => $template['type']];

                if ($template['type'] === 'photo_approved' || $template['type'] === 'photo_rejected') {
                    $photo = Photo::where('photographer_id', $photographer->id)->inRandomOrder()->first();
                    if ($photo) {
                        $message = str_replace('{photo_title}', $photo->title, $message);
                        $message = str_replace('{reason}', 'Qualité insuffisante', $message);
                        $data['photo_id'] = $photo->id;
                    }
                } elseif ($template['type'] === 'new_sale') {
                    $photo = Photo::where('photographer_id', $photographer->id)->inRandomOrder()->first();
                    if ($photo) {
                        $amount = rand(500, 10000);
                        $message = str_replace('{photo_title}', $photo->title, $message);
                        $message = str_replace('{amount}', number_format($amount, 0, ',', ' '), $message);
                        $data['photo_id'] = $photo->id;
                        $data['amount'] = $amount;
                    }
                } elseif ($template['type'] === 'withdrawal_completed' || $template['type'] === 'withdrawal_rejected') {
                    $amount = rand(5000, 100000);
                    $message = str_replace('{amount}', number_format($amount, 0, ',', ' '), $message);
                    $message = str_replace('{reason}', 'Informations de paiement incorrectes', $message);
                    $data['amount'] = $amount;
                } elseif ($template['type'] === 'new_follower') {
                    $follower = $buyers->random();
                    $message = str_replace('{user_name}', $follower->first_name . ' ' . $follower->last_name, $message);
                    $data['follower_id'] = $follower->id;
                } elseif ($template['type'] === 'monthly_report') {
                    $month = now()->subMonth()->format('F Y');
                    $message = str_replace('{month}', $month, $message);
                    $data['month'] = $month;
                }

                Notification::create([
                    'id' => Str::uuid(),
                    'user_id' => $photographer->id,
                    'type' => $template['type'],
                    'title' => $template['title'],
                    'message' => $message,
                    'data' => json_encode($data),
                    'is_read' => $isRead,
                    'read_at' => $isRead ? $createdAt->addHours(rand(1, 48)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $totalNotifications++;
            }
        }

        // Create notifications for buyers
        foreach ($buyers as $buyer) {
            $notificationsCount = rand(2, 5);

            for ($i = 0; $i < $notificationsCount; $i++) {
                $template = $buyerNotifications[array_rand($buyerNotifications)];
                $createdAt = now()->subDays(rand(1, 60));
                $isRead = rand(0, 10) > 4; // 60% read

                $message = $template['message'];
                $data = ['type' => $template['type']];

                if ($template['type'] === 'order_completed' || $template['type'] === 'order_failed') {
                    $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                    $amount = rand(1000, 50000);
                    $message = str_replace('{order_number}', $orderNumber, $message);
                    $message = str_replace('{amount}', number_format($amount, 0, ',', ' '), $message);
                    $data['order_number'] = $orderNumber;
                    $data['amount'] = $amount;
                } elseif ($template['type'] === 'new_photo_from_followed') {
                    $photographer = $photographers->random();
                    $photo = Photo::where('photographer_id', $photographer->id)->inRandomOrder()->first();
                    if ($photo) {
                        $message = str_replace('{photographer_name}', $photographer->first_name . ' ' . $photographer->last_name, $message);
                        $message = str_replace('{photo_title}', $photo->title, $message);
                        $data['photographer_id'] = $photographer->id;
                        $data['photo_id'] = $photo->id;
                    }
                }

                Notification::create([
                    'id' => Str::uuid(),
                    'user_id' => $buyer->id,
                    'type' => $template['type'],
                    'title' => $template['title'],
                    'message' => $message,
                    'data' => json_encode($data),
                    'is_read' => $isRead,
                    'read_at' => $isRead ? $createdAt->addHours(rand(1, 48)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $totalNotifications++;
            }
        }

        $this->command->info("✅ Notifications seeded: {$totalNotifications} total notifications");
    }
}
