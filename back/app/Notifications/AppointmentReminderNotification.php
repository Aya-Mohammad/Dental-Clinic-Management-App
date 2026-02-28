<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
class AppointmentReminderNotification extends Notification
{
    use Queueable;

    protected $hours;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(int $hours)
    {
        $this->hours = $hours;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        return FcmMessage::create()
        ->setNotification(FcmNotification::create()
            ->setTitle('Appointment Reminder')
            ->setBody("Your Appointment is in {$this->hours}")
        )
        ->setData([
            'hours_left' => (string) $this->hours
        ]);
        
    }

}
