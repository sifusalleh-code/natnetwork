<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Affiliate\Models\AffiliateRateVersion;
use App\Engines\Affiliate\Models\AffiliateSetting;
use App\Engines\Affiliate\Services\AffiliateSettingsService;
use App\Engines\Affiliate\Services\CommissionCalculator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AffiliateSettingsController extends Controller
{
    private const EXAMPLES = [300, 500, 1000, 2000, 5000, 10000];

    public function show(CommissionCalculator $calculator): View
    {
        $version = AffiliateRateVersion::current();

        return view('admin.affiliate.settings', [
            'version' => $version,
            'tiers' => old('tiers', $version->tiers),
            'cookieDays' => AffiliateSetting::cookieDays(),
            'task' => AffiliateSetting::weeklyTargets(),
            'examples' => collect(self::EXAMPLES)->map(fn (int $rm) => ['sale' => $rm, 'commission' => $calculator->totalCents($version->tiers, $rm * 100) / 100]),
            'history' => AffiliateRateVersion::query()->latest('id')->limit(10)->get(),
        ]);
    }

    public function updateRates(Request $request, AffiliateSettingsService $service): RedirectResponse
    {
        $data = $request->validate([
            'tiers' => ['required', 'array', 'min:1', 'max:20'],
            'tiers.*.up_to' => ['nullable', 'numeric', 'gt:0', 'max:100000000'],
            'tiers.*.rate' => ['required', 'numeric', 'gt:0', 'max:100'],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Sila sahkan anda faham kesan perubahan kadar.',
            'tiers.*.rate.*' => 'Kadar mesti antara 0.01% dan 100%.',
            'tiers.*.up_to.*' => 'Had atas mesti nombor positif.',
        ]);

        $tiers = [];
        $previous = 0.0;
        $rows = array_values($data['tiers']);
        foreach ($rows as $index => $row) {
            $isLast = $index === count($rows) - 1;
            $upTo = $row['up_to'] ?? null;
            if ($isLast && $upTo !== null && $upTo !== '') {
                throw ValidationException::withMessages(['tiers' => ['Tingkat terakhir mesti tanpa had atas (kosongkan medan "Hingga").']]);
            }
            if (! $isLast) {
                if ($upTo === null || $upTo === '' || (float) $upTo <= $previous) {
                    throw ValidationException::withMessages(['tiers' => ['Had atas setiap tingkat mesti diisi dan lebih besar daripada tingkat sebelumnya.']]);
                }
                $previous = (float) $upTo;
            }
            $tiers[] = ['up_to' => $isLast ? null : number_format((float) $upTo, 2, '.', ''), 'rate' => number_format((float) $row['rate'], 2, '.', '')];
        }

        $version = $service->saveRates(Auth::guard('admin')->user(), $tiers);

        return redirect()->route('admin.affiliate.settings')->with('status', $version
            ? 'Kadar komisyen baharu disimpan (versi #'.$version->id.'). Hanya terpakai untuk komisyen akan datang.'
            : 'Tiada perubahan pada kadar komisyen.');
    }

    public function updateCookie(Request $request, AffiliateSettingsService $service): RedirectResponse
    {
        $data = $request->validate(['cookie_days' => ['required', 'integer', 'min:1', 'max:365']]);
        $service->saveCookieDays(Auth::guard('admin')->user(), (int) $data['cookie_days']);

        return redirect()->route('admin.affiliate.settings')->with('status', 'Tempoh cookie disimpan: '.$data['cookie_days'].' hari.');
    }

    public function updateTask(Request $request, \App\Engines\Audit\Services\AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'weekly_share_target' => ['required', 'integer', 'min:0', 'max:1000'],
            'weekly_unique_click_target' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
        $setting = AffiliateSetting::query()->first() ?? AffiliateSetting::query()->create(['cookie_days' => AffiliateSetting::DEFAULT_COOKIE_DAYS]);
        $previous = $setting->only(['weekly_share_target', 'weekly_unique_click_target']);
        $setting->forceFill($data)->save();
        $audit->record('AFFILIATE_WEEKLY_TASK_CHANGED', Auth::guard('admin')->user(), $setting, $previous, $data);

        return back()->with('status', 'Task mingguan dikemas kini.');
    }
}
