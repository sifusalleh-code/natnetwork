<?php

namespace App\Http\Controllers;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Services\AffiliateProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AffiliateProfileController extends Controller
{
    public function show(AffiliateProfileService $profiles): View
    {
        $affiliate = $this->affiliate();

        return view('affiliate.profile', [
            'affiliate' => $affiliate,
            'missing' => $profiles->missingFields($affiliate),
            'states' => AffiliateProfileService::STATES,
            'banks' => AffiliateProfileService::BANKS,
        ]);
    }

    public function update(Request $request, AffiliateProfileService $profiles): RedirectResponse
    {
        $affiliate = $this->affiliate();
        $request->merge(['bank_account_number' => preg_replace('/\s+/', '', (string) $request->input('bank_account_number', '')) ?: null]);

        $data = $request->validate([
            'username' => $affiliate->username ? ['prohibited'] : ['required', 'string', 'regex:/^[a-z]{3,12}$/', Rule::unique('affiliates', 'username')],
            'phone' => ['required', 'string', 'max:50'],
            'state' => ['required', Rule::in(AffiliateProfileService::STATES)],
            'bank_name' => ['required', Rule::in(AffiliateProfileService::BANKS)],
            'bank_account_number' => [$affiliate->bank_account_last4 ? 'nullable' : 'required', 'regex:/^\d{6,30}$/'],
            'facebook_url' => ['nullable', 'string', 'max:300'],
            'instagram_url' => ['nullable', 'string', 'max:300'],
            'twitter_url' => ['nullable', 'string', 'max:300'],
        ], [
            'username.regex' => 'Username mesti 3 hingga 12 huruf kecil tanpa ruang.',
            'username.unique' => 'Username ini telah digunakan. Sila pilih yang lain.',
            'username.prohibited' => 'Username telah dikunci dan tidak boleh diubah.',
            'bank_account_number.regex' => 'No. Akaun Bank mesti 6 hingga 30 digit tanpa simbol.',
        ]);

        $affiliate = $profiles->update($affiliate, $data);

        return redirect()->route('affiliate.profile')->with('status', $affiliate->isProfileComplete()
            ? 'Profil berjaya dikemaskini.'
            : 'Profil disimpan. Sila lengkapkan: '.implode(', ', $profiles->missingFields($affiliate)).'.');
    }

    public function updateAvatar(Request $request, AffiliateProfileService $profiles): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'avatar.*' => 'Gambar mestilah JPG, PNG atau WebP dan tidak melebihi 5MB.',
        ]);

        $profiles->replaceAvatar($this->affiliate(), $request->file('avatar'));

        return redirect()->route('affiliate.profile')->with('status', 'Gambar profil berjaya dikemaskini.');
    }

    public function avatar(): StreamedResponse
    {
        $affiliate = $this->affiliate();
        abort_unless($affiliate->avatar_path && Storage::disk(AffiliateProfileService::AVATAR_DISK)->exists($affiliate->avatar_path), 404);

        return Storage::disk(AffiliateProfileService::AVATAR_DISK)->response($affiliate->avatar_path, null, ['Cache-Control' => 'private, no-store']);
    }

    private function affiliate(): Affiliate
    {
        /** @var Affiliate */
        return Auth::guard('affiliate')->user();
    }
}
