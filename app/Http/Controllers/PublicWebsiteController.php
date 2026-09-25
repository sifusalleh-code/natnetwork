<?php

namespace App\Http\Controllers;

use App\Engines\Affiliate\Models\AffiliateRateVersion;
use App\Engines\Communication\Services\EmailService;
use App\Engines\Affiliate\Services\CommissionCalculator;
use App\Engines\Pricing\Models\Addon;
use App\Engines\Pricing\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicWebsiteController extends Controller
{
    public function home(): View
    {
        return view('public.zip-home', ['services' => $this->catalogServices(), 'company' => config('company')]);
    }

    public function services(): View
    {
        $services = $this->catalogServices()->loadMissing('packages.packageAddons.addon');

        return view('public.services', ['services' => $services, 'addons' => Addon::query()->where('is_active', true)->whereNotNull('catalogue_order')->orderBy('catalogue_order')->get()]);
    }

    public function opportunity(CommissionCalculator $calculator): View
    {
        $version = AffiliateRateVersion::current();
        $examples = collect([1500, 3900, 5000, 6900, 12000, 15000, 20000])->map(fn (int $amount) => [
            'amount' => $amount,
            'commission' => $calculator->totalCents($version->tiers, $amount * 100) / 100,
        ]);
        $sample = $calculator->forPurchase($version->tiers, 0, 1200000);

        return view('public.opportunity', [
            'rateVersion' => $version,
            'hasPreviousVersion' => AffiliateRateVersion::query()->where('id', '<', $version->id)->exists(),
            'examples' => $examples,
            'sample' => $sample,
        ]);
    }

    public function contact(): View
    {
        $company = config('company');

        return view('public.contact', [
            'company' => $company,
            'mapUrl' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(implode(', ', $company['address_lines'])),
            'mapEmbedUrl' => 'https://www.google.com/maps?q='.rawurlencode(implode(', ', $company['address_lines'])).'&output=embed',
        ]);
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'regex:/^[^\r\n]+$/'],
            'company' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:120', 'regex:/^[^\r\n]+$/'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $body = implode("\n", [
            'Pertanyaan baharu daripada laman NatNetwork.',
            '',
            'Nama: '.$data['name'],
            'Syarikat: '.($data['company'] ?? '-'),
            'Email: '.$data['email'],
            'Telefon: '.($data['phone'] ?? '-'),
            'Subjek: '.$data['subject'],
            '',
            'Mesej:',
            $data['message'],
        ]);

        // Melalui EmailService (Resend jika aktif), sama seperti semua emel lain dalam sistem.
        if (! app(EmailService::class)->send(config('company.email'), 'Pertanyaan NatNetwork: '.$data['subject'], $body, 'CONTACT', false, $data['email'])) {
            return back()->withInput()->withErrors(['contact_form' => 'Pertanyaan tidak dapat dihantar sekarang. Sila hubungi kami melalui email atau telefon.']);
        }

        return redirect()->route('contact')->with('status', 'Terima kasih. Pertanyaan anda telah dihantar kepada NatNetwork Synergy.');
    }

    private function catalogServices()
    {
        return Service::query()->where('is_active', true)->with(['packages' => fn ($query) => $query->where('is_active', true)])->orderBy('display_order')->get();
    }
}
