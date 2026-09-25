<?php

namespace App\Http\Controllers;

use App\Engines\Affiliate\Models\AffiliatePoster;
use App\Engines\Affiliate\Models\AffiliateShare;
use App\Engines\Affiliate\Models\AffiliateWithdrawal;
use App\Engines\Affiliate\Services\AffiliateCustomerService;
use App\Engines\Affiliate\Services\AffiliateDashboardService;
use App\Engines\Affiliate\Services\AffiliatePosterService;
use App\Engines\Affiliate\Services\AffiliateWalletService;
use App\Engines\Affiliate\Services\AffiliateWeeklyTaskService;
use App\Engines\Affiliate\Models\AffiliateCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AffiliatePortalController extends Controller
{
    public function dashboard(AffiliateDashboardService $dashboard, AffiliatePosterService $posters): View
    {
        $affiliate = Auth::guard('affiliate')->user();

        return view('affiliate.dashboard', ['title' => 'Dashboard', 'active' => 'dashboard', 'data' => $dashboard->forAffiliate($affiliate), 'link' => $posters->referralLink($affiliate)]);
    }

    public function studioPoster(AffiliatePosterService $posters, AffiliateWeeklyTaskService $tasks): View
    {
        $affiliate = Auth::guard('affiliate')->user();
        $link = $posters->referralLink($affiliate);

        return view('affiliate.studio-poster', [
            'title' => 'Studio Poster', 'active' => 'studio-poster', 'link' => $link,
            'task' => $tasks->progress($affiliate),
            'posters' => AffiliatePoster::query()->where('is_active', true)->orderBy('display_order')->latest('id')->get()->map(fn (AffiliatePoster $p) => ['poster' => $p, 'caption' => $p->captionFor($link)]),
        ]);
    }

    public function posterImage(AffiliatePoster $poster): Response
    {
        abort_unless($poster->is_active && Storage::disk('local')->exists($poster->image_path), 404);

        return response()->file(Storage::disk('local')->path($poster->image_path), ['Content-Type' => $poster->image_mime, 'Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function share(Request $request, AffiliatePoster $poster, AffiliatePosterService $posters): Response|RedirectResponse|JsonResponse
    {
        abort_unless($poster->is_active, 404);
        $data = $request->validate(['channel' => ['required', Rule::in(array_keys(AffiliateShare::CHANNELS))]]);
        $affiliate = Auth::guard('affiliate')->user();
        $posters->recordShare($affiliate, $poster, $data['channel']);
        $link = $posters->referralLink($affiliate);

        if ($data['channel'] === 'download') {
            abort_unless(Storage::disk('local')->exists($poster->image_path), 404);
            $ext = AffiliatePosterService::MIMES[$poster->image_mime] ?? 'jpg';

            return response()->download(Storage::disk('local')->path($poster->image_path), 'natnetwork-'.\Illuminate\Support\Str::slug($poster->title).'.'.$ext, ['Content-Type' => $poster->image_mime]);
        }
        if ($data['channel'] === 'copy' || $request->expectsJson()) {
            return response()->json(['recorded' => true, 'intent' => $posters->intentUrl($data['channel'], $link, $poster->captionFor($link))]);
        }

        return redirect()->away($posters->intentUrl($data['channel'], $link, $poster->captionFor($link)));
    }

    public function wallet(AffiliateWalletService $wallet, AffiliateWeeklyTaskService $tasks): View
    {
        $affiliate = Auth::guard('affiliate')->user();

        return view('affiliate.wallet', [
            'title' => 'Wallet', 'active' => 'wallet', 'affiliate' => $affiliate,
            'summary' => $wallet->summary($affiliate),
            'task' => $tasks->progress($affiliate),
            'hasOpenRequest' => AffiliateWithdrawal::query()->where('affiliate_id', $affiliate->id)->where('status', AffiliateWithdrawal::STATUS_REQUESTED)->exists(),
            'withdrawals' => AffiliateWithdrawal::query()->where('affiliate_id', $affiliate->id)->latest('id')->limit(30)->get(),
            'commissions' => AffiliateCommission::query()->with(['customer:id,name', 'invoice:id,number'])->where('affiliate_id', $affiliate->id)->latest('id')->limit(30)->get(),
        ]);
    }

    public function requestWithdrawal(Request $request, AffiliateWalletService $wallet): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0', 'max:10000000', 'decimal:0,2']], ['amount.*' => 'Masukkan amaun yang sah (contoh 150.00).']);
        $withdrawal = $wallet->request(Auth::guard('affiliate')->user(), (string) $data['amount']);

        return redirect()->route('affiliate.wallet')->with('status', 'Permohonan withdrawal RM'.number_format((float) $withdrawal->amount, 2).' dihantar. Admin akan memproses bayaran ke akaun bank anda.');
    }

    public function customers(AffiliateCustomerService $customers): View
    {
        return view('affiliate.customers', [
            'title' => 'Pelanggan',
            'active' => 'customers',
            'customers' => $customers->forAffiliate(Auth::guard('affiliate')->user()),
        ]);
    }
}
