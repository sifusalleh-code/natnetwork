<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Audit\Services\AuditLogger;
use App\Http\Controllers\Admin\Concerns\DeletesEligibleAccounts;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountsController extends Controller
{
    use DeletesEligibleAccounts;

    public function clients(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('admin.accounts.clients', [
            'users' => User::query()->when($q, fn ($s) => $s->where(fn ($s) => $s->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))->latest('id')->paginate(30)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function bulkClients(Request $request, AuditLogger $audit): RedirectResponse
    {
        return $this->bulkAccountAction($request, $audit, User::class, 'admin.clients.index');
    }

    public function affiliates(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('admin.accounts.affiliates', [
            'affiliates' => Affiliate::query()->when($q, fn ($s) => $s->where(fn ($s) => $s->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('username', 'like', "%$q%")))->latest('id')->paginate(30)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function bulkAffiliates(Request $request, AuditLogger $audit): RedirectResponse
    {
        return $this->bulkAccountAction($request, $audit, Affiliate::class, 'admin.affiliates.index');
    }

    /**
     * Bulk Suspend/Activate/Delete akaun ahli (Client atau Affiliate). Delete hanya berjaya untuk
     * akaun tanpa sebarang transaksi/rekod berkaitan (lihat DeletesEligibleAccounts). Suspend/Activate
     * menyekat log masuk akaun melalui middleware Ensure*Authenticated — bukan hard state di luar kawalan.
     */
    private function bulkAccountAction(Request $request, AuditLogger $audit, string $modelClass, string $redirectRoute): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['suspend', 'activate', 'delete'])],
            'reason' => ['required_if:action,suspend', 'nullable', 'string', 'max:500'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Sila sahkan tindakan pukal ini.', 'reason.required_if' => 'Sebab wajib diisi untuk menggantung akaun.']);

        $admin = Auth::guard('admin')->user();
        $ids = array_unique(array_map('intval', $data['ids']));
        $done = 0;
        $skipped = 0;

        if ($data['action'] === 'delete') {
            // Pendaftaran/transaksi sandbox akaun ini dibersih dahulu (Keputusan #2) supaya akaun
            // yang HANYA ada sejarah sandbox benar-benar padam, bukan dilangkau.
            foreach ($ids as $id) {
                if ($modelClass === User::class) {
                    $this->purgeCustomerSandboxFootprint($id);
                } elseif ($modelClass === Affiliate::class) {
                    $this->purgeAffiliateSandboxFootprint($id);
                }
            }
            $result = $this->deleteEligible($modelClass, $ids);
            foreach ($result['deleted'] as $id) {
                $audit->record('ACCOUNT_DELETED', $admin, null, null, ['model' => class_basename($modelClass), 'id' => $id]);
            }
            $done = count($result['deleted']);
            $skipped = count($result['skipped']);
        } else {
            $suspend = $data['action'] === 'suspend';
            /** @var iterable<Model> $records */
            $records = $modelClass::query()->whereIn('id', $ids)->get();
            foreach ($records as $record) {
                if ($record->isSuspended() === $suspend) {
                    $skipped++;

                    continue;
                }
                $previous = ['suspended_at' => $record->suspended_at];
                $record->forceFill($suspend
                    ? ['suspended_at' => now(), 'suspended_reason' => $data['reason'], 'suspended_by_admin_id' => $admin->id]
                    : ['suspended_at' => null, 'suspended_reason' => null, 'suspended_by_admin_id' => null])->save();
                $audit->record($suspend ? 'ACCOUNT_SUSPENDED' : 'ACCOUNT_ACTIVATED', $admin, $record, $previous, ['suspended_at' => $record->suspended_at], $suspend ? $data['reason'] : null);
                $done++;
            }
        }

        $message = "{$done} akaun berjaya dikemas kini.";
        if ($skipped > 0) {
            $message .= " {$skipped} akaun dilangkau (".($data['action'] === 'delete' ? 'mempunyai rekod/transaksi berkaitan' : 'sudah pada status tersebut').').';
        }

        return redirect()->route($redirectRoute)->with('status', $message);
    }
}
