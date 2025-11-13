<?php

namespace App\Notifications;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhotoApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Photo $photo
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Photo approuvée')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Votre photo a été approuvée et est maintenant disponible à la vente.')
            ->line('Titre: ' . ($this->photo->title ?? 'Sans titre'))
            ->action('Voir la photo', url('/photographer/photos/' . $this->photo->id))
            ->line('Merci pour votre contribution !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'photo_id' => $this->photo->id,
            'title' => $this->photo->title,
            'message' => 'Votre photo a été approuvée'
        ];
    }
}
