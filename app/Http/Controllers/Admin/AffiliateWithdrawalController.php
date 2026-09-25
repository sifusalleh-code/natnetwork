<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Affiliate\Models\AffiliateWithdrawal;
use App\Engines\Affiliate\Services\AffiliateWalletService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliateWithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', AffiliateWithdrawal::STATUS_REQUESTED);
        $status = in_array($status, array_keys(AffiliateWithdrawal::LABELS), true) || $status === 'ALL' ? $status : AffiliateWithdrawal::STATUS_REQUESTED;

        return view('admin.affiliate.withdrawals', [
            'status' => $status,
            'counts' => AffiliateWithdrawal::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'withdrawals' => AffiliateWithdrawal::query()->with(['affiliate:id,name,email,username', 'processedBy:id,name'])
                ->when($status !== 'ALL', fn ($q) => $q->where('status', $status))->latest('id')->paginate(30)->withQueryString(),
        ]);
    }

    public function paid(Request $request, AffiliateWithdrawal $withdrawal, AffiliateWalletService $wallet): RedirectResponse
    {
        $data = $request->validate(['reference' => ['required', 'string', 'max:120'], 'confirm' => ['accepted']], ['confirm.accepted' => 'Sahkan transfer telah dibuat.']);
        $wallet->markPaid(Auth::guard('admin')->user(), $withdrawal, $data['reference']);

        return back()->with('status', 'Withdrawal #'.$withdrawal->id.' ditanda Dibayar.');
    }

    public function reject(Request $request, AffiliateWithdrawal $withdrawal, AffiliateWalletService $wallet): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $wallet->reject(Auth::guard('admin')->user(), $withdrawal, $data['reason']);

        return back()->with('status', 'Withdrawal #'.$withdrawal->id.' ditolak. Amaun dikembalikan ke baki affiliate.');
    }
}
