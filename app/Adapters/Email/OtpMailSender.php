<?php

namespace App\Adapters\Email;

use App\Engines\Communication\Services\EmailService;

class OtpMailSender
{
    public function __construct(private readonly EmailService $email)
    {
    }

    public function send(string $email, string $code): void
    {
        $this->email->send($email, 'Kod log masuk NatNetwork', "Kod log masuk NatNetwork anda: {$code}\n\nKod ini sah selama 10 minit dan hanya boleh digunakan sekali.", 'OTP', true);
    }
}
