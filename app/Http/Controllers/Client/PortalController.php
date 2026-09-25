<?php

namespace App\Http\Controllers\Client;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Project\Models\Project;
use App\Engines\ProjectContent\Models\ProjectContentItem;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Services\SchedulingService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(): View
    {
        $user = $this->user();
        $quotations = $this->quotationsFor($user)->get();
        $invoices = Invoice::query()->where('customer_user_id', $user->id)->latest('issued_at')->get();

        $actions = collect();
        foreach ($quotations->filter->isAwaitingCustomer() as $q) {
            $actions->push(['title' => 'Semak & terima quotation '.$q->number, 'detail' => 'RM '.number_format((float) $q->total_amount, 2).' · sah sehingga '.$q->valid_until?->format('d/m/Y'), 'url' => route('client.quotations.show', $q), 'cta' => 'Semak']);
        }
        $projects = Project::query()->where('customer_user_id', $user->id)->latest('id')->get();
        foreach ($quotations->where('status', Quotation::STATUS_ACCEPTED) as $q) {
            if (! $projects->contains('quotation_id', $q->id) && ! app(SchedulingService::class)->currentHold($q)) {
                $actions->push(['title' => 'Pilih slot mula projek untuk '.$q->number, 'detail' => 'Slot dipegang sementara semasa anda membayar deposit.', 'url' => route('client.quotations.slot', $q), 'cta' => 'Pilih slot']);
            }
        }
        $pendingContent = ProjectContentItem::query()->with('project')->whereIn('project_id', $projects->pluck('id'))
            ->whereIn('status', [ProjectContentItem::REQUESTED, ProjectContentItem::NEEDS_UPDATE])->get();
        foreach ($pendingContent as $item) {
            $actions->push(['title' => ($item->status === ProjectContentItem::NEEDS_UPDATE ? 'Kemas kini: ' : 'Hantar: ').$item->title, 'detail' => $item->project->number.($item->blocking ? ' · wajib sebelum kerja diteruskan' : ''), 'url' => route('client.projects.show', $item->project), 'cta' => 'Buka']);
        }
        foreach ($invoices->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID]) as $inv) {
            $actions->push(['title' => 'Bayar invois '.$inv->number, 'detail' => 'Baki RM '.number_format($inv->outstandingCents() / 100, 2), 'url' => route('client.billing.invoice', $inv), 'cta' => 'Bayar']);
        }

        $payments = Payment::query()->with('invoice')->whereIn('invoice_id', $invoices->pluck('id'))->where('status', Payment::STATUS_PAID)->latest('paid_at')->limit(5)->get();
        $activity = collect()
            ->merge($invoices->take(5)->map(fn ($i) => ['at' => $i->issued_at, 'text' => 'Invois '.$i->number.' dikeluarkan (RM '.number_format((float) $i->total, 2).')']))
            ->merge($payments->map(fn ($p) => ['at' => $p->paid_at, 'text' => 'Bayaran RM '.$p->amount().' untuk '.$p->invoice->number.' diterima']))
            ->merge($quotations->filter(fn ($q) => $q->sent_at)->map(fn ($q) => ['at' => $q->accepted_at ?? $q->sent_at, 'text' => $q->accepted_at ? 'Quotation '.$q->number.' diterima' : 'Quotation '.$q->number.' dihantar kepada anda']))
            ->sortByDesc('at')->take(6)->values();

        return view('client.portal.dashboard', [
            'actions' => $actions,
            'outstanding' => $invoices->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])->sum(fn ($i) => $i->outstandingCents()) / 100,
            'paid' => $invoices->sum(fn ($i) => (float) $i->amount_paid),
            'activity' => $activity,
            'activeProject' => $projects->first(fn (Project $p) => ! $p->isClosed()),
        ]);
    }

    public static function quotationsFor(User $user)
    {
        return Quotation::query()->whereHas('request', fn ($q) => $q->where('customer_user_id', $user->id))
            ->whereNotIn('status', [Quotation::STATUS_DRAFT, Quotation::STATUS_REVIEW_REQUIRED])->latest('id');
    }

    private function user(): User
    {
        /** @var User */
        return Auth::guard('client')->user();
    }
}
