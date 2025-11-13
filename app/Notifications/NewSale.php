<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSale extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $photographerItems = $this->order->items()
            ->whereHas('photo', function ($query) use ($notifiable) {
                $query->where('photographer_id', $notifiable->id);
            })
            ->get();

        $totalAmount = $photographerItems->sum('photographer_amount');

        return (new MailMessage)
            ->subject('Nouvelle vente de vos photos')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Vous avez réalisé une nouvelle vente !')
            ->line('Nombre de photos vendues: ' . $photographerItems->count())
            ->line('Montant total: ' . number_format($totalAmount, 2) . ' €')
            ->line('Commande #' . $this->order->order_number)
            ->action('Voir les détails', url('/photographer/sales/' . $this->order->id))
            ->line('Merci de votre confiance !');
    }

    public function toArray(object $notifiable): array
    {
        $photographerItems = $this->order->items()
            ->whereHas('photo', function ($query) use ($notifiable) {
                $query->where('photographer_id', $notifiable->id);
            })
            ->get();

        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'items_count' => $photographerItems->count(),
            'total_amount' => $photographerItems->sum('photographer_amount'),
            'message' => 'Nouvelle vente de ' . $photographerItems->count() . ' photo(s)'
        ];
    }
}
