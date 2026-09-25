<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\AffiliateRateVersion;
use App\Engines\Affiliate\Models\AffiliateSetting;
use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Identity\Models\Admin;
use Illuminate\Support\Facades\DB;

class AffiliateSettingsService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * Simpan jadual kadar sebagai VERSI BAHARU. Komisyen sedia ada kekal dengan versi lama;
     * versi baharu hanya untuk komisyen akan datang.
     *
     * @param list<array{up_to: ?string, rate: string}> $tiers
     */
    public function saveRates(Admin $admin, array $tiers): ?AffiliateRateVersion
    {
        return DB::transaction(function () use ($admin, $tiers): ?AffiliateRateVersion {
            $current = AffiliateRateVersion::current();
            if ($current->tiers == $tiers) {
                return null;
            }

            $version = AffiliateRateVersion::query()->create(['tiers' => $tiers, 'created_by_admin_id' => $admin->id, 'effective_from' => now()]);
            $this->audit->record('AFFILIATE_RATES_CHANGED', $admin, $version, ['version_id' => $current->id, 'tiers' => $current->tiers], ['version_id' => $version->id, 'tiers' => $tiers]);

            return $version;
        });
    }

    public function saveCookieDays(Admin $admin, int $days): void
    {
        DB::transaction(function () use ($admin, $days): void {
            $setting = AffiliateSetting::query()->lockForUpdate()->first() ?? AffiliateSetting::query()->create(['cookie_days' => AffiliateSetting::DEFAULT_COOKIE_DAYS]);
            if ((int) $setting->cookie_days === $days) {
                return;
            }
            $previous = (int) $setting->cookie_days;
            $setting->forceFill(['cookie_days' => $days])->save();
            $this->audit->record('AFFILIATE_COOKIE_DAYS_CHANGED', $admin, $setting, ['cookie_days' => $previous], ['cookie_days' => $days]);
        });
    }
}
