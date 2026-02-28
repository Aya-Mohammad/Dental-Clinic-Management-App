<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = (new Factory())->withServiceAccount(__DIR__.'/../../storage/app/firebase/firebase_credentials.json')->createMessaging();
    }

    public function sendNotification($token, $title, $message, $data=[])
    {
        $notification = Notification::create($title, $message);
        $cloudMessage = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData($data);

        return $this->messaging->send($cloudMessage);
    }
}
