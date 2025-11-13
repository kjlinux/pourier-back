<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusMessages = [
            'pending' => 'en attente',
            'processing' => 'en cours de traitement',
            'completed' => 'terminée',
            'cancelled' => 'annulée',
        ];

        $message = (new MailMessage)
            ->subject('Mise à jour de votre commande')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Le statut de votre commande a été mis à jour.')
            ->line('Commande #' . $this->order->order_number)
            ->line('Nouveau statut: ' . ($statusMessages[$this->newStatus] ?? $this->newStatus));

        if ($this->newStatus === 'completed') {
            $message->line('Vos photos sont maintenant disponibles au téléchargement.')
                ->action('Télécharger mes photos', url('/orders/' . $this->order->id . '/download'));
        } else {
            $message->action('Voir ma commande', url('/orders/' . $this->order->id));
        }

        return $message->line('Merci pour votre achat !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => 'Votre commande a été mise à jour'
        ];
    }
}
