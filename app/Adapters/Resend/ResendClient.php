<?php

namespace App\Adapters\Resend;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Resend Adapter: hantar emel melalui API Resend (https://resend.com/docs/api-reference/emails/send-email). */
class ResendClient
{
    public const ENDPOINT = 'https://api.resend.com/emails';

    /** @return string ID emel daripada Resend */
    public function send(string $apiKey, string $from, string $to, string $subject, string $text, ?string $html = null, ?string $replyTo = null): string
    {
        $response = Http::withToken($apiKey)->acceptJson()->timeout(10)->post(self::ENDPOINT, array_filter([
            'from' => $from,
            'to' => [$to],
            'subject' => $subject,
            'text' => $text,
            'html' => $html,
            'reply_to' => $replyTo,
        ]));

        if (! $response->successful() || ! $response->json('id')) {
            throw new RuntimeException('Resend '.$response->status().': '.mb_substr((string) ($response->json('message') ?? $response->body()), 0, 300));
        }

        return (string) $response->json('id');
    }
}
