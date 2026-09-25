<?php

namespace App\Engines\Billing\Services;

use App\Engines\Billing\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /** NAT-{TYPE}-YYYY-NNNN untuk production, TEST-{TYPE}-YYYY-NNNN untuk sandbox. Null/tidak diketahui dianggap production. */
    public function next(string $type, ?bool $sandbox): string
    {
        $prefix = ($sandbox ? 'TEST-' : 'NAT-').$type;
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($prefix, $year): string {
            $sequence = DocumentSequence::query()->where('prefix', $prefix)->where('year', $year)->lockForUpdate()->first()
                ?? DocumentSequence::query()->create(['prefix' => $prefix, 'year' => $year, 'last_number' => 0]);
            $sequence->increment('last_number');

            return sprintf('%s-%d-%04d', $prefix, $year, $sequence->last_number);
        });
    }
}
