<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function sendSms($to, $message)
    {
        return $this->client->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message,
        ]);
    }

    // App\Services\TwilioService.php
    public function sendVoiceVerification(string $toPhoneNumber, string $code): string
    {
        $twimlUrl = route('twilio.voice.twiml', ['code' => $code]);

        $call = $this->client->calls->create(
            $toPhoneNumber,
            config('services.twilio.from'),
            ['url' => $twimlUrl]
        );

        return $call->sid;
    }

    /*public function sendVoiceVerification($toPhoneNumber, $verificationCode) {
        $sid    = env('TWILIO_SID');
        $token  = env('TWILIO_AUTH_TOKEN');
        $twilioNumber = env('TWILIO_PHONE_NUMBER');

        $client = new Client($sid, $token);

        $twimlUrl = route('twilio.voice.twiml', ['code' => $verificationCode]);

        $call = $client->calls->create(
            $toPhoneNumber,
            $twilioNumber,
            [
                'url' => $twimlUrl
            ]
        );

        return $call->sid;
    }*/
}