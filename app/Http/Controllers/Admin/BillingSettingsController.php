<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Pricing\Models\Addon;
use App\Engines\Billing\Services\BillingSettingsService;
use App\Engines\Billing\Services\BillplzPaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingSettingsController extends Controller
{
    public function show(): View
    {
        return view('admin.billing.settings', [
            'settings' => BillingSetting::current(),
            'callbackUrl' => BillplzPaymentService::publicUrl('billing.billplz.callback'),
            'returnUrl' => BillplzPaymentService::publicUrl('billing.billplz.return'),
            'addons' => Addon::query()->where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'price_label']),
        ]);
    }

    public function updateCredentials(Request $request, BillingSettingsService $service, string $mode): RedirectResponse
    {
        $mode = strtoupper($mode);
        abort_unless(in_array($mode, BillingSetting::MODES, true), 404);

        $data = $request->validate([
            'api_key' => ['nullable', 'string', 'max:200'],
            'collection_id' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
            'x_signature_key' => ['nullable', 'string', 'max:200'],
        ]);

        $service->updateCredentials(Auth::guard('admin')->user(), $mode, $data);

        return redirect()->route('admin.billing.settings')->with('status', 'Kunci Billplz '.$mode.' disimpan.');
    }

    public function switchMode(Request $request, BillingSettingsService $service): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(BillingSetting::MODES)],
            'confirm' => ['accepted'],
            'reason' => ['required', 'string', 'max:500'],
        ], ['confirm.accepted' => 'Sila sahkan anda faham kesan perubahan mod.', 'reason.required' => 'Sebab perubahan wajib diisi.']);

        $service->switchMode(Auth::guard('admin')->user(), $data['mode'], $data['reason']);

        return redirect()->route('admin.billing.settings')->with('status', 'Mod pembayaran kini '.$data['mode'].'.');
    }

    /** Ganjaran bayar penuh (100%): diskaun %, diskaun RM atau add-on percuma. Hanya pilihan baharu terkesan; invois sedia ada tidak berubah. */
    public function updateReward(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'type' => ['required_if:enabled,1', 'nullable', Rule::in(array_keys(BillingSetting::REWARD_TYPES))],
            'value' => ['nullable', 'numeric', 'min:0.01', 'max:999999', 'required_if:type,percent', 'required_if:type,fixed'],
            'addon_id' => ['nullable', 'required_if:type,addon', Rule::exists('addons', 'id')->where('is_active', true)],
        ], ['type.required_if' => 'Sila pilih jenis ganjaran.', 'value.required_if' => 'Sila isi nilai diskaun.', 'addon_id.required_if' => 'Sila pilih add-on percuma.']);
        if (($data['type'] ?? null) === 'percent' && (float) $data['value'] > 100) {
            return back()->withErrors(['value' => 'Diskaun peratus tidak boleh melebihi 100%.'])->withInput();
        }

        $settings = BillingSetting::current();
        $previous = $settings->only(['full_payment_reward_enabled', 'full_payment_reward_type', 'full_payment_reward_value', 'full_payment_reward_addon_id']);
        $type = $data['type'] ?? null;
        $settings->forceFill([
            'full_payment_reward_enabled' => (bool) ($data['enabled'] ?? false),
            'full_payment_reward_type' => $type,
            'full_payment_reward_value' => in_array($type, ['percent', 'fixed'], true) ? $data['value'] : null,
            'full_payment_reward_addon_id' => $type === 'addon' ? $data['addon_id'] : null,
        ])->save();
        $audit->record('FULL_PAYMENT_REWARD_CHANGED', Auth::guard('admin')->user(), $settings, $previous, $settings->only(array_keys($previous)));

        return redirect()->route('admin.billing.settings')->with('status', 'Ganjaran bayar penuh disimpan.');
    }
}
