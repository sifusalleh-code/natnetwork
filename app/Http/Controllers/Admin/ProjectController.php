<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Refund;
use App\Engines\Billing\Services\ProjectPaymentService;
use App\Engines\Billing\Services\RefundService;
use App\Engines\Project\Models\Project;
use App\Engines\Project\Models\ProjectMilestone;
use App\Engines\Project\Services\ProjectService;
use App\Engines\ProjectContent\Models\ProjectContentItem;
use App\Engines\ProjectContent\Models\ProjectFile;
use App\Engines\ProjectContent\Services\ContentService;
use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Sales\Models\Order;
use App\Engines\Scheduling\Services\SchedulingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function orders(): View
    {
        return view('admin.projects.orders', ['orders' => Order::query()->with(['customer', 'quotation', 'slotHold'])->latest('id')->paginate(25)]);
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.projects.index', [
            'projects' => Project::query()->with('customer')->when($status, fn ($q) => $q->where('status', $status))->latest('id')->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Project $project): View
    {
        $project->load(['customer', 'order.slotHold', 'quotation', 'milestones', 'statusLogs', 'contentItems.files', 'files', 'finalInvoice']);

        return view('admin.projects.show', [
            'project' => $project,
            'invoices' => $invoices = ProjectPaymentService::allProjectInvoices($project->quotation_id),
            'transitions' => Project::TRANSITIONS[$project->status] ?? [],
            'refunds' => Refund::query()->where('project_id', $project->id)->with('invoice')->latest('id')->get(),
            'refundable' => RefundService::eligible($project) ? $invoices->filter(fn (Invoice $i) => $i->refundableCents() > 0) : collect(),
            'changes' => ChangeRequest::query()->where('project_id', $project->id)->with('invoice')->latest('id')->get(),
        ]);
    }

    public function start(Project $project, ProjectService $projects): RedirectResponse
    {
        $projects->start(Auth::guard('admin')->user(), $project);

        return back()->with('status', 'Projek dimulakan (START PROJECT).');
    }

    public function transition(Request $request, Project $project, ProjectService $projects): RedirectResponse
    {
        $data = $request->validate(['to' => ['required', Rule::in(array_keys(Project::TRANSITIONS))], 'reason' => ['nullable', 'string', 'max:500']]);
        $projects->transition(Auth::guard('admin')->user(), $project, $data['to'], $data['reason'] ?? null);

        return back()->with('status', 'Status projek dikemas kini.');
    }

    public function complete(Request $request, Project $project, ProjectService $projects): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Sila sahkan penyelesaian projek.']);
        $projects->complete(Auth::guard('admin')->user(), $project);

        return back()->with('status', 'Projek ditandakan SELESAI. Tempoh sokongan bermula.');
    }

    public function completeMilestone(ProjectMilestone $milestone, ProjectService $projects): RedirectResponse
    {
        $project = $projects->completeMilestone(Auth::guard('admin')->user(), $milestone);

        return back()->with('status', 'Milestone selesai. Progress kini '.$project->progress.'%.'.($project->final_invoice_id ? ' Invois akhir telah dijana.' : ''));
    }

    public function requestContent(Request $request, Project $project, ContentService $content): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'], 'kind' => ['required', Rule::in(['FILE', 'INFO'])],
            'description' => ['nullable', 'string', 'max:1000'], 'blocking' => ['nullable', 'boolean'],
        ]);
        $content->request(Auth::guard('admin')->user(), $project, $data['title'], $data['kind'], (bool) ($data['blocking'] ?? false), $data['description'] ?? null);

        return back()->with('status', 'Permintaan bahan dihantar kepada pelanggan.');
    }

    public function reviewContent(Request $request, ProjectContentItem $item, ContentService $content): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['accept', 'update'])], 'reason' => ['nullable', 'string', 'max:500']]);
        $content->review(Auth::guard('admin')->user(), $item, $data['decision'] === 'accept', $data['reason'] ?? null);

        return back()->with('status', 'Semakan disimpan.');
    }

    public function upload(Request $request, Project $project, ContentService $content): RedirectResponse
    {
        $data = $request->validate([
            'file' => ContentService::uploadRules(),
            'visibility' => ['required', Rule::in([ProjectFile::CLIENT, ProjectFile::INTERNAL])],
            'is_deliverable' => ['nullable', 'boolean'],
        ]);
        $content->adminUpload(Auth::guard('admin')->user(), $project, $request->file('file'), $data['visibility'], (bool) ($data['is_deliverable'] ?? false));

        return back()->with('status', 'Fail dimuat naik.');
    }

    public function updateMilestones(Request $request, Project $project, ProjectService $projects): RedirectResponse
    {
        $data = $request->validate([
            'milestones' => ['required', 'array', 'min:1', 'max:30'],
            'milestones.*.name' => ['nullable', 'string', 'max:120'],
            'milestones.*.client_label' => ['nullable', 'string', 'max:120'],
            'milestones.*.weight' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $projects->replaceMilestones(Auth::guard('admin')->user(), $project, $data['milestones']);

        return back()->with('status', 'Milestone dikemas kini.');
    }

    public function reschedule(Request $request, Project $project, SchedulingService $scheduling): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'weeks' => ['required', 'integer', 'min:1', 'max:104'],
            'reason' => ['required', 'string', 'max:500'],
            'override' => ['nullable', 'boolean'],
        ]);
        abort_if($project->isClosed(), 422, 'Projek telah ditutup.');
        $scheduling->reschedule(Auth::guard('admin')->user(), $project->quotation, $data['start_date'], (int) $data['weeks'], (bool) ($data['override'] ?? false), $data['reason']);

        return back()->with('status', 'Slot projek dijadualkan semula. Pelanggan telah dimaklumkan.');
    }

    public function refund(Request $request, Project $project, RefundService $refunds): RedirectResponse
    {
        $data = $request->validate([
            'amounts' => ['required', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
            'transfer_reference' => ['required', 'string', 'max:120'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Sila sahkan wang telah dipindahkan kepada pelanggan.']);
        $created = $refunds->refundProject(Auth::guard('admin')->user(), $project, $data['amounts'], $data['reason'], $data['transfer_reference']);

        return back()->with('status', 'Refund '.$created->pluck('number')->implode(', ').' direkod. Projek dibatalkan dan slot dilepaskan.');
    }

    public function download(ProjectFile $file, ContentService $content): StreamedResponse
    {
        return $content->download($file);
    }
}
