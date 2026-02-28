<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailNotification extends Notification
{
    use Queueable;
    public $message;
    public $subject;
    public $fromEmail;
    public $mailer;
    private $otp;

    public function __construct($otp, $message = 'Use the code below for verification', $subject = 'OTP verification')
    {
        $this->message = $message;
        $this->subject = $subject;
        $this->fromEmail = 'test@gmail.com';
        $this->mailer = 'smtp';
        $this->otp = $otp;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->mailer($this->mailer)
                    ->subject($this->subject)
                    ->line($this->message)
                    ->line($this->otp);
    }
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
