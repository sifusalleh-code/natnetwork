<?php

namespace App\Engines\Communication\Listeners;

use App\Engines\Billing\Events\InvoiceIssued;
use App\Engines\Billing\Events\InvoicePaid;
use App\Engines\Communication\Services\NotificationService;
use App\Engines\Project\Events\MilestoneCompleted;
use App\Engines\Project\Events\ProjectStatusChanged;
use App\Engines\ProjectContent\Events\ContentItemChanged;
use App\Engines\Sales\Events\OrderConfirmed;
use App\Engines\Sales\Events\QuotationSent;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Billing\Events\RefundRecorded;
use App\Engines\Sales\Events\ChangeRequestAssessed;
use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Scheduling\Events\SlotRescheduled;
use Illuminate\Events\Dispatcher;

/** Communication mendengar acara engine lain dan mencipta notifikasi portal pelanggan. */
class SendPortalNotifications
{
    public function __construct(private readonly NotificationService $notify)
    {
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            QuotationSent::class => 'quotationSent',
            InvoiceIssued::class => 'invoiceIssued',
            InvoicePaid::class => 'invoicePaid',
            OrderConfirmed::class => 'orderConfirmed',
            ProjectStatusChanged::class => 'projectStatus',
            MilestoneCompleted::class => 'milestone',
            ContentItemChanged::class => 'content',
            RefundRecorded::class => 'refunded',
            ChangeRequestAssessed::class => 'changeAssessed',
            SlotRescheduled::class => 'rescheduled',
        ];
    }

    public function quotationSent(QuotationSent $e): void
    {
        $this->notify->toClient($e->quotation->request()->value('customer_user_id'), 'QUOTATION', 'Quotation '.$e->quotation->number.' sedia untuk semakan', 'Jumlah RM '.number_format((float) $e->quotation->total_amount, 2), route('client.quotations.show', $e->quotation));
    }

    public function invoiceIssued(InvoiceIssued $e): void
    {
        $this->notify->toClient($e->invoice->customer_user_id, 'PAYMENT', 'Invois '.$e->invoice->number.' dikeluarkan', 'Jumlah RM '.number_format((float) $e->invoice->total, 2), route('client.billing.invoice', $e->invoice));
    }

    public function invoicePaid(InvoicePaid $e): void
    {
        $this->notify->toClient($e->invoice->customer_user_id, 'PAYMENT', 'Bayaran untuk '.$e->invoice->number.' diterima', 'Terima kasih. Resit tersedia di Billing.', route('client.billing.invoice', $e->invoice));
    }

    public function orderConfirmed(OrderConfirmed $e): void
    {
        $this->notify->toClient($e->order->customer_user_id, 'ORDER', 'Pesanan '.$e->order->number.' disahkan', 'Slot projek anda telah ditempah.', route('client.projects'));
    }

    public function projectStatus(ProjectStatusChanged $e): void
    {
        if ($e->from === null) {
            return;
        }
        $this->notify->toClient($e->project->customer_user_id, 'PROJECT', $e->project->number.': '.$e->project->clientLabel(), $e->reason ?: $e->project->clientExplanation(), route('client.projects.show', $e->project));
    }

    public function milestone(MilestoneCompleted $e): void
    {
        $this->notify->toClient($e->project->customer_user_id, 'PROGRESS', $e->project->number.' kini '.$e->project->progress.'%', 'Peringkat "'.$e->milestone->client_label.'" dikemas kini.', route('client.projects.show', $e->project));
    }

    public function content(ContentItemChanged $e): void
    {
        $messages = ['REQUESTED' => 'Bahan diminta: ', 'NEEDS_UPDATE' => 'Perlu kemas kini: ', 'ACCEPTED' => 'Bahan diterima: '];
        if (isset($messages[$e->action])) {
            $project = $e->item->project;
            $this->notify->toClient($project->customer_user_id, 'FILE', $messages[$e->action].$e->item->title, $e->action === 'NEEDS_UPDATE' ? $e->item->admin_note : $project->number, route('client.projects.show', $project));
        }
    }

    public function refunded(RefundRecorded $e): void
    {
        $this->notify->toClient($e->project->customer_user_id, 'PAYMENT', 'Refund RM '.number_format($e->totalCents / 100, 2).' untuk '.$e->project->number, 'Refund telah dipindahkan ke akaun anda. Projek dibatalkan. Butiran di Billing.', route('client.billing'));
    }

    public function changeAssessed(ChangeRequestAssessed $e): void
    {
        $cr = $e->changeRequest;
        $body = match ($cr->status) {
            ChangeRequest::QUOTED => 'Kerja tambahan RM '.number_format((float) $cr->amount, 2).($cr->extra_weeks ? ' · +'.$cr->extra_weeks.' minggu' : '').'. Sila semak dan luluskan.',
            ChangeRequest::IN_SCOPE => 'Perubahan ini termasuk dalam skop tanpa caj tambahan.',
            default => $cr->admin_note,
        };
        $this->notify->toClient($cr->customer_user_id, 'CHANGE', 'Permintaan perubahan '.$cr->number.': '.$cr->label(), $body, route('client.projects.show', $cr->project_id).'#perubahan');
    }

    public function rescheduled(SlotRescheduled $e): void
    {
        $customerId = Quotation::query()->find($e->hold->quotation_id)?->request()->value('customer_user_id');
        $this->notify->toClient($customerId, 'PROJECT', 'Jadual projek dikemas kini', 'Tarikh mula baharu: minggu '.$e->hold->start_date->format('d/m/Y').'. '.$e->reason, route('client.projects'));
    }
}
