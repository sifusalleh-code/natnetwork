<?php

namespace App\Engines\Affiliate\Services;

/**
 * Kiraan komisyen bertingkat (incremental) atas jumlah terkumpul pelanggan.
 * Semua nilai dalam sen (integer) untuk mengelak ralat perpuluhan.
 */
class CommissionCalculator
{
    /**
     * Komisyen bagi satu pembelian = C(terkumpul selepas) − C(terkumpul sebelum), guna jadual kadar yang diberi.
     *
     * @param list<array{up_to: ?string, rate: string}> $tiers
     * @return array{amount_cents: int, breakdown: list<array{from: string, to: string, rate: string, portion: string}>}
     */
    public function forPurchase(array $tiers, int $beforeCents, int $grossCents): array
    {
        $afterCents = $beforeCents + $grossCents;
        $amount = $this->totalCents($tiers, $afterCents) - $this->totalCents($tiers, $beforeCents);

        $breakdown = [];
        $lower = 0;
        foreach ($tiers as $tier) {
            $upper = $tier['up_to'] === null ? PHP_INT_MAX : $this->cents($tier['up_to']);
            $from = max($lower, $beforeCents);
            $to = min($upper, $afterCents);
            if ($to > $from) {
                $breakdown[] = ['from' => $this->money($from), 'to' => $this->money($to), 'rate' => $tier['rate'], 'portion' => $this->money($to - $from)];
            }
            $lower = $upper;
            if ($lower >= $afterCents) {
                break;
            }
        }

        return ['amount_cents' => $amount, 'breakdown' => $breakdown];
    }

    /** Jumlah komisyen kumulatif C(x) bagi jumlah terkumpul x sen, dibundarkan ke sen terdekat. */
    public function totalCents(array $tiers, int $cumulativeCents): int
    {
        $sum = 0; // sen × basis point
        $lower = 0;
        foreach ($tiers as $tier) {
            $upper = $tier['up_to'] === null ? PHP_INT_MAX : $this->cents($tier['up_to']);
            if ($cumulativeCents > $lower) {
                $sum += (min($cumulativeCents, $upper) - $lower) * (int) round(((float) $tier['rate']) * 100);
            }
            $lower = $upper;
            if ($lower >= $cumulativeCents) {
                break;
            }
        }

        return intdiv($sum + 5000, 10000);
    }

    private function cents(string $value): int { return (int) round(((float) $value) * 100); }
    private function money(int $cents): string { return number_format($cents / 100, 2, '.', ''); }
}
