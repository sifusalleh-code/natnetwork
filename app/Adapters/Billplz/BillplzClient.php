<?php

namespace App\Adapters\Billplz;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Adapter Billplz API v3. Bukan sumber kebenaran kewangan — hanya menghantar permintaan dan mengesahkan tandatangan.
 */
class BillplzClient
{
    public const BASE_URLS = [
        'SANDBOX' => 'https://www.billplz-sandbox.com/api',
        'PRODUCTION' => 'https://www.billplz.com/api',
    ];

    /**
     * @param array{api_key: string, collection_id: string} $credentials
     * @param array<string, string|int> $bill
     * @return array<string, mixed>
     */
    public function createBill(string $mode, array $credentials, array $bill): array
    {
        $response = Http::withBasicAuth($credentials['api_key'], '')
            ->asForm()
            ->acceptJson()
            ->timeout(20)
            ->post(self::BASE_URLS[$mode].'/v3/bills', array_merge(['collection_id' => $credentials['collection_id']], $bill));

        if (! $response->successful() || ! is_string($response->json('id')) || ! is_string($response->json('url'))) {
            $message = $response->json('error.message');
            throw new RuntimeException('Billplz menolak permintaan bil (HTTP '.$response->status().')'.(is_string($message) ? ': '.$message : (is_array($message) ? ': '.implode(', ', $message) : '')));
        }

        return $response->json();
    }

    /** Tandatangan X-Signature untuk callback (POST): semua pasangan kecuali x_signature, disusun tanpa mengira huruf besar/kecil, disambung dengan '|'. */
    public function callbackSignature(array $payload, string $key): string
    {
        unset($payload['x_signature']);
        $pairs = [];
        foreach ($payload as $name => $value) {
            $pairs[] = $name.(is_scalar($value) || $value === null ? (string) $value : '');
        }

        return $this->sign($pairs, $key);
    }

    /** Tandatangan X-Signature untuk redirect (GET): parameter billplz[...] diratakan menjadi 'billplz'+kunci. */
    public function redirectSignature(array $billplz, string $key): string
    {
        unset($billplz['x_signature']);
        $pairs = [];
        foreach ($billplz as $name => $value) {
            $pairs[] = 'billplz'.$name.(is_scalar($value) || $value === null ? (string) $value : '');
        }

        return $this->sign($pairs, $key);
    }

    public function verify(string $expected, mixed $given): bool
    {
        return is_string($given) && $given !== '' && hash_equals($expected, $given);
    }

    private function sign(array $pairs, string $key): string
    {
        usort($pairs, fn (string $a, string $b): int => strcasecmp($a, $b));

        return hash_hmac('sha256', implode('|', $pairs), $key);
    }
}
