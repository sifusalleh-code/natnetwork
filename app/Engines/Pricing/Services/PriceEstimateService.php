<?php

namespace App\Engines\Pricing\Services;

use App\Engines\Pricing\Models\Addon;
use App\Engines\Pricing\Models\PackageAddon;
use App\Engines\Pricing\Models\ServicePackage;
use Illuminate\Support\Collection;

/**
 * Pricing Engine (pemilik harga semasa): kos Start Project = harga pakej + harga add-on yang dipilih.
 * Harga add-on ikut pakej (package_addons); jika add-on tiada dalam senarai pakej, harga standard katalog digunakan.
 * Harga "starting" (RM…+) diambil pada nilai tersenarai. Pakej/add-on "quote" atau bulanan perlu semakan admin.
 */
class PriceEstimateService
{
    /** @param list<int|string> $addonIds */
    public function estimate(?ServicePackage $package, array $addonIds = []): array
    {
        $addons = $this->activeAddons($addonIds);
        $lines = [];
        $total = 0;
        $needsReview = ! $package || ! $this->autoQuotable($package);

        if ($package) {
            $cents = $package->price_amount !== null ? (int) round((float) $package->price_amount * 100) : null;
            $lines[] = ['kind' => 'package', 'id' => $package->id, 'description' => $package->name, 'label' => $package->price_label, 'cents' => $cents];
            $total += (int) $cents;
        }
        $offered = $package ? PackageAddon::query()->where('service_package_id', $package->id)->where('is_active', true)->get()->keyBy('addon_id') : collect();
        foreach ($addons as $addon) {
            $price = $offered->get($addon->id) ?? $addon;
            $cents = $price->price_amount !== null ? (int) round((float) $price->price_amount * 100) : null;
            $monthly = $price->price_type === 'monthly';
            $name = $price instanceof PackageAddon ? $price->displayName() : $addon->name;
            $lines[] = ['kind' => 'addon', 'id' => $addon->id, 'description' => 'Add-on: '.$name, 'label' => $price->price_label, 'cents' => $cents, 'monthly' => $monthly];
            $total += $monthly ? 0 : (int) $cents;
            $needsReview = $needsReview || $cents === null || $monthly;
        }

        return [
            'lines' => $lines,
            'total_cents' => $total,
            'needs_review' => $needsReview,
            'package_id' => $package?->id,
            'addon_ids' => $addons->pluck('id')->values()->all(),
        ];
    }

    /** Pakej berharga tetap/permulaan (bukan "quote", bukan langganan bulanan) boleh terus dijana quotation. */
    public function autoQuotable(ServicePackage $package): bool
    {
        return in_array($package->price_type, ['fixed', 'starting'], true)
            && (float) $package->price_amount > 0
            && ! str_contains(mb_strtolower((string) $package->price_label), 'bulan');
    }

    /** @return Collection<int, Addon> */
    public function activeAddons(array $ids): Collection
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, fn ($id) => is_numeric($id)))));

        return $ids ? Addon::query()->whereIn('id', $ids)->where('is_active', true)->orderBy('display_order')->get() : collect();
    }

    /** Anggaran minggu pembangunan daripada "3–5 hari" (hari bekerja → minggu). */
    public function estimatedWeeks(?ServicePackage $package): int
    {
        preg_match_all('/\d+/', (string) $package?->delivery_estimate, $m);
        $days = $m[0] ? max(array_map('intval', $m[0])) : 20;

        return max(1, (int) ceil($days / 5));
    }
}
