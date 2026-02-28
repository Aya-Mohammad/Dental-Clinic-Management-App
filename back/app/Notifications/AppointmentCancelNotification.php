<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
class AppointmentCancelNotification extends Notification
{
    use Queueable;

    protected $appointment_id;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($appointment_id)
    {
        $this->appointment_id = $appointment_id;
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
        ->setTitle('Appointment Canceled')
        ->setBody('Your Appointment has been canceled')
    )
    ->setData([
        'appointment_id' => $this->appointment_id
    ]);
    }
}
