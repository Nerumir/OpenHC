<?php

namespace App\Notifications;

use App\Models\Service;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ServicesDownNotification extends Notification
{
    public function __construct(private Collection $downServices) {}

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $message = (new MailMessage)
            ->subject('[Alert] Services Down — ' . now()->format('d/m/Y H:i'))
            ->greeting('Services Alert')
            ->line('The following ' . $this->downServices->count() . ' service(s) are currently down:');

        foreach ($this->downServices as $service) {
            $message->line("• {$service->display_name} ({$service->protocol}://{$service->host}:{$service->port})");
        }

        return $message
            ->line('Please investigate immediately.')
            ->line('Checked at: ' . now()->format('d/m/Y H:i:s'));
    }
}
