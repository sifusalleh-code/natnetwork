<?php

namespace App\Engines\Project\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\ProjectPaymentService;
use App\Engines\Billing\Services\DocumentNumberService;
use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Events\MilestoneCompleted;
use App\Engines\Project\Events\ProjectReachedReview;
use App\Engines\Project\Events\ProjectStatusChanged;
use App\Engines\Project\Models\Project;
use App\Engines\Project\Models\ProjectMilestone;
use App\Engines\Project\Models\ProjectStatusLog;
use App\Engines\ProjectContent\Models\ProjectContentItem;
use App\Engines\Sales\Models\Order;
use App\Engines\Scheduling\Services\SchedulingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Project Engine: status melalui command terkawal; progress hanya daripada milestone. */
class ProjectService
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
        private readonly SchedulingService $scheduling,
    ) {
    }

    /** Order confirmed → projek WAITING_TO_START dengan milestone & item kandungan di-snapshot daripada template. Idempotent. */
    public function createFromOrder(Order $order): Project
    {
        $existing = Project::query()->where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $quotation = $order->quotation()->firstOrFail();
        $templateKey = $quotation->price_snapshot['project_template'] ?? 'website';
        $template = config("project_templates.$templateKey") ?? config('project_templates.website');
        $total = (float) $quotation->total_amount;

        $project = Project::query()->create([
            'number' => $this->numbers->next('PRJ', false),
            'order_id' => $order->id,
            'quotation_id' => $quotation->id,
            'customer_user_id' => $order->customer_user_id,
            'name' => $quotation->price_snapshot['selected_package']['name'] ?? ($quotation->items()[0]['description'] ?? 'Projek '.$quotation->number),
            'template' => $templateKey,
            'status' => Project::WAITING_TO_START,
            'progress' => 0,
            'planned_start_date' => $order->slotHold?->start_date,
            'support_days' => $this->supportDays($total),
        ]);

        foreach ($template['milestones'] as $i => [$name, $label, $weight]) {
            $project->milestones()->create(['position' => $i + 1, 'name' => $name, 'client_label' => $label, 'weight' => $weight]);
        }
        foreach ($template['content'] as [$title, $kind, $blocking]) {
            $project->contentItems()->create(['title' => $title, 'kind' => $kind, 'status' => ProjectContentItem::REQUESTED, 'blocking' => $blocking]);
        }
        ProjectStatusLog::query()->create(['project_id' => $project->id, 'from_status' => null, 'to_status' => Project::WAITING_TO_START, 'reason' => 'Order '.$order->number.' disahkan', 'created_at' => now()]);
        ProjectStatusChanged::dispatch($project, null, Project::WAITING_TO_START);

        return $project;
    }

    public function supportDays(float $total): int
    {
        foreach (config('project_templates.support_days') as [$below, $days]) {
            if ($below === null || $total < $below) {
                return $days;
            }
        }

        return 0;
    }

    /** START PROJECT — hanya admin, hanya dari WAITING_TO_START, selepas order disahkan. Tamatkan kelayakan refund standard. */
    public function start(Admin $admin, Project $project): Project
    {
        return DB::transaction(function () use ($admin, $project): Project {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            if ($project->status !== Project::WAITING_TO_START || $project->order()->value('status') !== Order::CONFIRMED) {
                throw ValidationException::withMessages(['project' => ['Projek hanya boleh dimulakan dari status WAITING TO START dengan order yang disahkan.']]);
            }

            $project->forceFill(['started_at' => now(), 'started_by_admin_id' => $admin->id])->save();
            $this->setStatus($admin, $project, Project::IN_PROGRESS, 'START PROJECT');
            $this->audit->record('PROJECT_STARTED', $admin, $project, ['status' => Project::WAITING_TO_START], ['status' => Project::IN_PROGRESS]);

            return $project;
        });
    }

    /**
     * Ubah milestone sebelum START PROJECT sahaja: nama, label pelanggan, berat; tambah/buang.
     * Jumlah berat mesti tepat 100. Selepas projek bermula, milestone dikunci.
     *
     * @param list<array{name: string, client_label: string, weight: int|string}> $rows
     */
    public function replaceMilestones(Admin $admin, Project $project, array $rows): Project
    {
        return DB::transaction(function () use ($admin, $project, $rows): Project {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            if ($project->status !== Project::WAITING_TO_START || $project->isStarted()) {
                throw ValidationException::withMessages(['milestones' => ['Milestone hanya boleh diubah sebelum START PROJECT.']]);
            }
            $rows = array_values(array_filter($rows, fn ($r) => filled($r['name'] ?? null)));
            if ($rows === []) {
                throw ValidationException::withMessages(['milestones' => ['Sekurang-kurangnya satu milestone diperlukan.']]);
            }
            $total = array_sum(array_map(fn ($r) => (int) $r['weight'], $rows));
            if ($total !== 100) {
                throw ValidationException::withMessages(['milestones' => ["Jumlah berat milestone mesti 100% (sekarang {$total}%)."]]);
            }

            $previous = $project->milestones()->get(['name', 'client_label', 'weight'])->toArray();
            $project->milestones()->delete();
            foreach ($rows as $i => $r) {
                $project->milestones()->create(['position' => $i + 1, 'name' => $r['name'], 'client_label' => $r['client_label'] ?: $r['name'], 'weight' => (int) $r['weight']]);
            }
            $this->audit->record('MILESTONES_CHANGED', $admin, $project, ['milestones' => $previous], ['milestones' => $rows]);

            return $project;
        });
    }

    /** Refund sebelum START → projek dibatalkan (slot dilepaskan melalui transition). */
    public function cancelForRefund(Admin $admin, Project $project, string $reason): void
    {
        $project = $project->fresh();
        if (! $project->isClosed()) {
            $this->transition($admin, $project, Project::CANCELLED, 'Refund: '.$reason);
        }
    }

    public function transition(Admin $admin, Project $project, string $to, ?string $reason): Project
    {
        return DB::transaction(function () use ($admin, $project, $to, $reason): Project {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            if (! in_array($to, Project::TRANSITIONS[$project->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => ["Peralihan {$project->status} → {$to} tidak dibenarkan."]]);
            }
            if (in_array($to, Project::REASON_REQUIRED, true) && blank($reason)) {
                throw ValidationException::withMessages(['reason' => ['Sebab wajib diisi untuk status ini.']]);
            }
            if ($to === Project::IN_PROGRESS && ! $project->isStarted()) {
                throw ValidationException::withMessages(['status' => ['Guna START PROJECT untuk memulakan projek.']]);
            }

            $from = $project->status;
            $this->setStatus($admin, $project, $to, $reason);
            if ($to === Project::CANCELLED) {
                $this->scheduling->releaseForQuotation($project->quotation_id);
            }
            $this->audit->record('PROJECT_STATUS_CHANGED', $admin, $project, ['status' => $from], ['status' => $to], $reason);

            return $project;
        });
    }

    /** Admin tandakan milestone selesai → progress dikira semula. Lintasan 80% kali pertama → acara untuk Billing (invois akhir, sekali). */
    public function completeMilestone(Admin $admin, ProjectMilestone $milestone): Project
    {
        $crossed = false;
        $project = DB::transaction(function () use ($admin, $milestone, &$crossed): Project {
            $project = Project::query()->lockForUpdate()->findOrFail($milestone->project_id);
            $milestone = ProjectMilestone::query()->lockForUpdate()->findOrFail($milestone->id);
            if (! $project->isStarted() || $project->isClosed()) {
                throw ValidationException::withMessages(['milestone' => ['Milestone hanya boleh diselesaikan selepas START PROJECT dan sebelum projek ditutup.']]);
            }
            if ($milestone->completed_at) {
                return $project;
            }

            $milestone->forceFill(['completed_at' => now(), 'completed_by_admin_id' => $admin->id])->save();
            $previous = $project->progress;
            $new = (int) ProjectMilestone::query()->where('project_id', $project->id)->whereNotNull('completed_at')->sum('weight');
            $project->forceFill(['progress' => min(100, $new)])->save();
            $this->audit->record('MILESTONE_COMPLETED', $admin, $milestone, ['progress' => $previous], ['progress' => $project->progress, 'milestone' => $milestone->name]);

            $crossed = $previous < 80 && $project->progress >= 80 && ! $project->final_invoice_id;
            MilestoneCompleted::dispatch($project, $milestone);

            return $project;
        });

        if ($crossed) {
            ProjectReachedReview::dispatch($project->fresh());
        }

        return $project->fresh();
    }

    /** Selesai: semua milestone siap (100%), tiada baki bayaran projek. Lepaskan slot; sokongan percuma bermula. */
    public function complete(Admin $admin, Project $project): Project
    {
        return DB::transaction(function () use ($admin, $project): Project {
            $project = Project::query()->lockForUpdate()->findOrFail($project->id);
            if ($project->status !== Project::READY_FOR_HANDOVER) {
                throw ValidationException::withMessages(['project' => ['Projek mesti berstatus READY FOR HANDOVER sebelum ditandakan selesai.']]);
            }
            if ($project->progress < 100) {
                throw ValidationException::withMessages(['project' => ['Semua milestone mesti selesai (100%).']]);
            }
            $unpaid = ProjectPaymentService::allProjectInvoices($project->quotation_id)
                ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])->isNotEmpty();
            if ($unpaid) {
                throw ValidationException::withMessages(['project' => ['Masih ada invois projek yang belum dibayar.']]);
            }

            $project->forceFill(['completed_at' => now()])->save();
            $this->setStatus($admin, $project, Project::COMPLETED, 'Projek selesai');
            $this->scheduling->releaseForQuotation($project->quotation_id);
            $this->audit->record('PROJECT_COMPLETED', $admin, $project, null, ['support_days' => $project->support_days]);

            return $project;
        });
    }

    private function setStatus(?Admin $admin, Project $project, string $to, ?string $reason): void
    {
        $from = $project->status;
        $project->forceFill(['status' => $to, 'status_note' => $reason])->save();
        ProjectStatusLog::query()->create(['project_id' => $project->id, 'from_status' => $from, 'to_status' => $to, 'reason' => $reason, 'admin_id' => $admin?->id, 'created_at' => now()]);
        ProjectStatusChanged::dispatch($project, $from, $to, $reason);
    }
}
