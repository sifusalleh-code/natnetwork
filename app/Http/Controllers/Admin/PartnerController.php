<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Partnership\Models\Partner;
use App\Engines\Partnership\Models\PartnerCapital;
use App\Engines\Partnership\Models\PartnerEarning;
use App\Engines\Partnership\Models\PartnerPoolEntry;
use App\Engines\Partnership\Models\PartnerSetting;
use App\Engines\Partnership\Services\PartnerService;
use App\Http\Controllers\Admin\Concerns\DeletesEligibleAccounts;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PartnerController extends Controller
{
    use DeletesEligibleAccounts;

    public function index(Request $request): View
    {
        $status = $request->query('status', 'ALL');

        return view('admin.partners.index', [
            'partners' => Partner::query()->when($status !== 'ALL', fn ($q) => $q->where('status', $status))->latest('id')->paginate(25)->withQueryString(),
            'status' => $status,
            'settings' => PartnerSetting::current(),
            'totals' => [
                'capital' => (float) PartnerCapital::query()->where('status', PartnerCapital::ACTIVE)->where('is_sandbox', false)->sum('amount'),
                'pool' => (float) PartnerPoolEntry::query()->sum('pool_amount'),
                'allocated' => (float) PartnerPoolEntry::query()->sum('allocated_amount'),
            ],
        ]);
    }

    public function show(Partner $partner): View
    {
        return view('admin.partners.show', [
            'partner' => $partner,
            'capitals' => $partner->capitals()->with('invoice')->get(),
            'earnings' => PartnerEarning::query()->with('entry.invoice')->where('partner_id', $partner->id)->latest('id')->limit(50)->get(),
            'payouts' => $partner->payouts()->latest('id')->get(),
            'balanceCents' => $partner->balanceCents(),
        ]);
    }

    public function review(Request $request, Partner $partner, PartnerService $partners): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject', 'suspend'])], 'review_note' => ['nullable', 'string', 'max:500']]);
        $partners->review(Auth::guard('admin')->user(), $partner, $data['decision'], $data['review_note'] ?? null);

        return back()->with('status', 'Status partner dikemas kini.');
    }

    public function payout(Request $request, Partner $partner, PartnerService $partners): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'], 'transfer_reference' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:500'], 'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Sila sahkan wang telah dipindahkan.']);
        $payout = $partners->recordPayout(Auth::guard('admin')->user(), $partner, (string) $data['amount'], $data['transfer_reference'], $data['note'] ?? null);

        return back()->with('status', 'Pengeluaran '.$payout->number.' direkod.');
    }

    /**
     * Bulk approve/reject/suspend/activate/delete partner. Approve/reject/suspend/activate melalui
     * PartnerService::review() yang sama seperti tindakan satu-per-satu (audit, notifikasi partner
     * turut terjana). Delete hanya berjaya untuk partner tanpa sebarang modal/earning/payout
     * (hampir semua partner sedia ada akan dilangkau — ini disengajakan, bukan pepijat).
     */
    public function bulk(Request $request, PartnerService $partners, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['approve', 'reject', 'suspend', 'activate', 'delete'])],
            'reason' => ['nullable', 'string', 'max:500'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Sila sahkan tindakan pukal ini.']);

        $admin = Auth::guard('admin')->user();
        $ids = array_unique(array_map('intval', $data['ids']));
        $done = 0;
        $skipped = 0;

        if ($data['action'] === 'delete') {
            $result = $this->deleteEligible(Partner::class, $ids);
            foreach ($result['deleted'] as $id) {
                $audit->record('ACCOUNT_DELETED', $admin, null, null, ['model' => 'Partner', 'id' => $id]);
            }
            $done = count($result['deleted']);
            $skipped = count($result['skipped']);
        } else {
            $decision = $data['action'] === 'activate' ? 'approve' : $data['action'];
            foreach (Partner::query()->whereIn('id', $ids)->get() as $partner) {
                try {
                    $partners->review($admin, $partner, $decision, $data['reason'] ?? null);
                    $done++;
                } catch (ValidationException) {
                    $skipped++; // contoh: sebab wajib untuk reject/suspend tetapi tidak diisi
                }
            }
        }

        $message = "{$done} partner berjaya dikemas kini.";
        if ($skipped > 0) {
            $message .= " {$skipped} partner dilangkau (".($data['action'] === 'delete' ? 'mempunyai rekod modal/earning/payout berkaitan' : 'sebab wajib diisi untuk tindakan ini').').';
        }

        return back()->with('status', $message);
    }

    public function pool(): View
    {
        return view('admin.partners.pool', ['entries' => PartnerPoolEntry::query()->with('invoice')->latest('id')->paginate(40)]);
    }

    public function updateSettings(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'pool_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'min_capital' => ['required', 'numeric', 'min:1'],
            'max_total_capital' => ['required', 'numeric', 'min:1'],
            'program_enabled' => ['nullable', 'boolean'],
        ]);
        $enabled = (bool) ($data['program_enabled'] ?? false);
        $settings = PartnerSetting::current();
        $previous = $settings->only(['program_enabled', 'pool_percent', 'min_capital', 'max_total_capital']);
        $settings->update(['pool_percent' => $data['pool_percent'], 'min_capital' => $data['min_capital'], 'max_total_capital' => $data['max_total_capital'], 'program_enabled' => $enabled]);
        $audit->record('PARTNER_SETTINGS_CHANGED', Auth::guard('admin')->user(), $settings, $previous, $settings->only(['program_enabled', 'pool_percent', 'min_capital', 'max_total_capital']));

        return back()->with('status', 'Tetapan Program Partnership disimpan. Peratus baharu terpakai untuk jualan seterusnya.');
    }
}
