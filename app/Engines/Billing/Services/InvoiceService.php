<?php

namespace App\Engines\Billing\Services;

use App\Engines\Billing\Events\InvoiceIssued;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    /**
     * Cipta invois. Mod (sandbox/production) diambil daripada tetapan aktif semasa invois dicipta dan kekal.
     *
     * @param array{name: string, email: string, phone?: ?string, company?: ?string} $customer
     * @param list<array{description: string, quantity: int, unit_price: string|float}> $items
     */
    public function issue(string $type, array $customer, array $items, ?User $customerUser = null, ?string $dueAt = null, ?string $sourceType = null, ?int $sourceId = null): Invoice
    {
        if (! in_array($type, Invoice::TYPES, true) || $items === []) {
            throw new InvalidArgumentException('Jenis invois atau item tidak sah.');
        }

        $lines = [];
        $subtotalCents = 0;
        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];
            $unitCents = (int) round(((float) $item['unit_price']) * 100);
            if ($quantity < 1 || $unitCents < 1) {
                throw new InvalidArgumentException('Kuantiti dan harga item mesti positif.');
            }
            $lineCents = $quantity * $unitCents;
            $subtotalCents += $lineCents;
            $lines[] = ['description' => $item['description'], 'quantity' => $quantity, 'unit_price' => $this->money($unitCents), 'line_total' => $this->money($lineCents)];
        }

        $sandbox = BillingSetting::current()->isSandbox();

        $invoice = DB::transaction(fn (): Invoice => Invoice::query()->create([
            'number' => $this->numbers->next('INV', $sandbox),
            'is_sandbox' => $sandbox,
            'type' => $type,
            'status' => Invoice::STATUS_ISSUED,
            'customer_user_id' => $customerUser?->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'seller_snapshot' => config('company'),
            'customer_snapshot' => $customer,
            'items_snapshot' => $lines,
            'subtotal' => $this->money($subtotalCents),
            'tax_amount' => '0.00',
            'total' => $this->money($subtotalCents),
            'amount_paid' => '0.00',
            'issued_at' => now(),
            'due_at' => $dueAt,
        ]));

        InvoiceIssued::dispatch($invoice);

        return $invoice;
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
