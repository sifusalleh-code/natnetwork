<?php

namespace App\Engines\Billing\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Identity\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingSettingsService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** Kemas kini kunci Billplz bagi satu mod. Medan kosong mengekalkan nilai sedia ada. */
    public function updateCredentials(Admin $admin, string $mode, array $data): BillingSetting
    {
        $prefix = $mode === BillingSetting::MODE_PRODUCTION ? 'production' : 'sandbox';

        return DB::transaction(function () use ($admin, $mode, $data, $prefix): BillingSetting {
            $settings = BillingSetting::query()->lockForUpdate()->firstOrFail();
            $changed = [];

            foreach (['api_key', 'collection_id', 'x_signature_key'] as $field) {
                $value = trim((string) ($data[$field] ?? ''));
                if ($value !== '' && $value !== $settings->{$prefix.'_'.$field}) {
                    $settings->{$prefix.'_'.$field} = $value;
                    $changed[] = $field;
                }
            }

            if ($changed) {
                $settings->save();
                $this->audit->record('BILLPLZ_CREDENTIALS_UPDATED', $admin, $settings, null, ['mode' => $mode, 'fields' => $changed]);
            }

            return $settings;
        });
    }

    public function switchMode(Admin $admin, string $mode, ?string $reason): BillingSetting
    {
        return DB::transaction(function () use ($admin, $mode, $reason): BillingSetting {
            $settings = BillingSetting::query()->lockForUpdate()->firstOrFail();

            if ($settings->active_mode === $mode) {
                return $settings;
            }

            if (! $settings->hasCredentials($mode)) {
                throw ValidationException::withMessages(['mode' => ['Lengkapkan API Key, Collection ID dan X-Signature Key bagi mod '.$mode.' sebelum mengaktifkannya.']]);
            }

            $previous = $settings->active_mode;
            $settings->forceFill(['active_mode' => $mode])->save();
            $this->audit->record('BILLING_MODE_CHANGED', $admin, $settings, ['active_mode' => $previous], ['active_mode' => $mode], $reason);

            return $settings;
        });
    }
}
