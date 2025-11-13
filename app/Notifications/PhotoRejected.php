<?php

namespace App\Notifications;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PhotoRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Photo $photo,
        public ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Photo refusée')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Votre photo a été refusée.')
            ->line('Titre: ' . ($this->photo->title ?? 'Sans titre'));

        if ($this->reason) {
            $message->line('Raison: ' . $this->reason);
        }

        return $message
            ->line('Vous pouvez soumettre une nouvelle photo à tout moment.')
            ->action('Voir mes photos', url('/photographer/photos'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'photo_id' => $this->photo->id,
            'title' => $this->photo->title,
            'reason' => $this->reason,
            'message' => 'Votre photo a été refusée'
        ];
    }
}
