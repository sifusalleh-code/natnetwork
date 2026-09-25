<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Padam akaun ahli secara pukal (bulk) — HANYA jika rekod itu tiada sebarang transaksi/rekod
 * berkaitan (quotation, order, projek, komisen, modal, dll). Ini menguatkuasakan AGENTS.md §14:
 * "Jangan letakkan Edit/Delete pada semua record secara membuta tuli" — rekod yang mempunyai
 * sejarah transaksi dilangkau (bukan dipaksa padam), bukan diveto secara manual bagi setiap jadual
 * berkaitan, sebaliknya kekangan foreign key pangkalan data sendiri (restrictOnDelete) menjadi
 * pemeriksa muktamad: jika ada rujukan, padam akan gagal dan direkod sebagai "dilangkau".
 */
trait DeletesEligibleAccounts
{
    /**
     * @param  list<int>  $ids
     * @return array{deleted: list<int>, skipped: list<int>}
     */
    private function deleteEligible(string $modelClass, array $ids): array
    {
        $deleted = [];
        $skipped = [];

        foreach ($ids as $id) {
            /** @var Model|null $record */
            $record = $modelClass::query()->find($id);
            if (! $record) {
                continue;
            }
            try {
                DB::transaction(function () use ($record): void {
                    $record->delete();
                });
                $deleted[] = $id;
            } catch (QueryException) {
                // Kekangan foreign key (restrictOnDelete): akaun ini mempunyai rekod/transaksi berkaitan.
                $skipped[] = $id;
            }
        }

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }
}
