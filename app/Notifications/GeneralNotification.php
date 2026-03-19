<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    private $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function via($notifiable): array
    {
        // Database ရော Mail ပါ ပို့မယ်
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject($this->details['subject'])
                    ->greeting('မင်္ဂလာပါ ' . $notifiable->fullname)
                    ->line($this->details['body'])
                    ->action($this->details['actionText'], $this->details['actionURL'])
                    ->line('ကျေးဇူးတင်ပါသည်။');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->details['subject'],
            'message' => $this->details['body'],
        ];
    }
}