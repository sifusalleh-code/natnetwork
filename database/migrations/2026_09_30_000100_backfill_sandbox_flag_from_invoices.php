<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill data (bukan sekadar skema): migration `2026_09_28_000100` menambah lajur `is_sandbox`
 * pada quotations/orders/projects/slot_holds/change_requests dengan default `false` — ini secara
 * tidak sengaja menandakan SEMUA rekod SEDIA ADA (termasuk yang sebenarnya sandbox) sebagai
 * production, kerana `invoices.is_sandbox` (lajur lama, betul sejak awal) tidak diselaraskan semasa
 * migration itu. Kesannya: akaun pelanggan yang transaksinya sandbox sebenar gagal dipadam kerana
 * dianggap ada rekod production (dilaporkan Owner 26 Sep 2026 — "ini bukan transaksi production").
 *
 * Migration ini menyelaraskan is_sandbox quotation daripada invois DEPOSIT/FINAL_PAYMENT/
 * ADDITIONAL_CHARGE yang betul-betul sandbox, kemudian mengalir semula ke order/projek/slot
 * hold/change request seperti sepatutnya sejak awal.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sandboxQuotationIds = DB::table('invoices')
            ->where('source_type', 'Quotation')
            ->where('is_sandbox', true)
            ->pluck('source_id')
            ->unique()
            ->values();

        if ($sandboxQuotationIds->isNotEmpty()) {
            DB::table('quotations')->whereIn('id', $sandboxQuotationIds)->update(['is_sandbox' => true]);
        }

        $allSandboxQuotationIds = DB::table('quotations')->where('is_sandbox', true)->pluck('id');
        if ($allSandboxQuotationIds->isNotEmpty()) {
            DB::table('orders')->whereIn('quotation_id', $allSandboxQuotationIds)->update(['is_sandbox' => true]);
            DB::table('projects')->whereIn('quotation_id', $allSandboxQuotationIds)->update(['is_sandbox' => true]);
            DB::table('slot_holds')->whereIn('quotation_id', $allSandboxQuotationIds)->update(['is_sandbox' => true]);
            DB::table('change_requests')->whereIn('quotation_id', $allSandboxQuotationIds)->update(['is_sandbox' => true]);
        }
    }

    public function down(): void
    {
        // Backfill data sahaja — tiada rollback bermakna (tidak boleh bezakan nilai asal
        // sebelum backfill daripada nilai yang telah dibetulkan).
    }
};
