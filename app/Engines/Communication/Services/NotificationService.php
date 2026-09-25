<?php

namespace App\Engines\Communication\Services;

use App\Engines\Communication\Models\PortalNotification;
use App\Engines\Partnership\Models\Partner;
use App\Models\User;

/** Communication: pemilik tunggal notifikasi portal. Setiap notifikasi pelanggan turut dihantar ke emel (Resend). */
class NotificationService
{
    public function __construct(private readonly EmailService $email)
    {
    }

    public function toClient(?int $userId, string $category, string $title, ?string $body = null, ?string $url = null): void
    {
        if (! $userId) {
            return;
        }

        PortalNotification::query()->create(['recipient_type' => 'CLIENT', 'recipient_id' => $userId, 'category' => $category, 'title' => $title, 'body' => $body, 'url' => $url]);

        $address = User::query()->whereKey($userId)->value('email');
        if ($address) {
            $text = trim($title."\n\n".($body ?? '')."\n\n".($url ? 'Lihat di portal: '.$url : 'Log masuk portal: '.route('client.login')));
            $this->email->queueAfterCommit($address, $title, $text, $category);
        }
    }

    public function toPartner(?int $partnerId, string $category, string $title, ?string $body = null, ?string $url = null): void
    {
        if (! $partnerId) {
            return;
        }

        PortalNotification::query()->create(['recipient_type' => 'PARTNER', 'recipient_id' => $partnerId, 'category' => $category, 'title' => $title, 'body' => $body, 'url' => $url]);

        $address = Partner::query()->whereKey($partnerId)->value('email');
        if ($address) {
            $this->email->queueAfterCommit($address, $title, trim($title."\n\n".($body ?? '')."\n\n".($url ? 'Lihat di portal: '.$url : '')), $category);
        }
    }

    public function unreadFor(string $type, int $id): int
    {
        return PortalNotification::query()->for($type, $id)->whereNull('read_at')->count();
    }

    public function unreadForClient(int $userId): int
    {
        return PortalNotification::query()->for('CLIENT', $userId)->whereNull('read_at')->count();
    }
}
