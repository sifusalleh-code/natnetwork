<?php

namespace App\Http\Controllers\Client;

use App\Engines\Project\Models\Project;
use App\Engines\ProjectContent\Models\ProjectContentItem;
use App\Engines\ProjectContent\Models\ProjectFile;
use App\Engines\ProjectContent\Services\ContentService;
use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Sales\Services\ChangeRequestService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Portal pelanggan: projek, bahan & fail. Setiap akses menyemak pemilikan; fail INTERNAL tidak pernah dipaparkan. */
class ProjectController extends Controller
{
    public function index(): View
    {
        return view('client.portal.projects', ['projects' => Project::query()->where('customer_user_id', Auth::guard('client')->id())->latest('id')->get()]);
    }

    public function show(Project $project): View
    {
        $this->authorizeProject($project);
        $project->load(['milestones', 'contentItems.files' => fn ($q) => $q->where('visibility', ProjectFile::CLIENT)]);

        return view('client.portal.project', [
            'project' => $project,
            'changes' => ChangeRequest::query()->where('project_id', $project->id)->with('invoice')->latest('id')->get(),
            'deliverables' => $project->files()->where('visibility', ProjectFile::CLIENT)->whereNull('content_item_id')->get(),
        ]);
    }

    public function files(): View
    {
        $projectIds = Project::query()->where('customer_user_id', Auth::guard('client')->id())->pluck('id');

        return view('client.portal.files', [
            'items' => ProjectContentItem::query()->with('project')->whereIn('project_id', $projectIds)->orderByRaw("CASE WHEN status IN ('REQUESTED','NEEDS_UPDATE') THEN 0 ELSE 1 END")->latest('id')->get(),
            'files' => ProjectFile::query()->with(['project', 'item'])->whereIn('project_id', $projectIds)->where('visibility', ProjectFile::CLIENT)->latest('id')->get(),
        ]);
    }

    public function submitInfo(Request $request, ProjectContentItem $item, ContentService $content): RedirectResponse
    {
        $this->authorizeItem($item);
        $data = $request->validate(['info_text' => ['required', 'string', 'max:5000']]);
        $content->submitInfo($item, $data['info_text']);

        return back()->with('status', 'Maklumat dihantar untuk semakan.');
    }

    public function submitFile(Request $request, ProjectContentItem $item, ContentService $content): RedirectResponse
    {
        $this->authorizeItem($item);
        $request->validate(['file' => ContentService::uploadRules()]);
        $content->submitFile($item, $request->file('file'));

        return back()->with('status', 'Fail dimuat naik untuk semakan.');
    }

    public function requestHelp(ProjectContentItem $item, ContentService $content): RedirectResponse
    {
        $this->authorizeItem($item);
        $content->requestHelp($item);

        return back()->with('status', 'Permintaan bantuan dihantar. Pasukan kami akan menghubungi anda.');
    }

    public function requestChange(Request $request, Project $project, ChangeRequestService $changes): RedirectResponse
    {
        $this->authorizeProject($project);
        $data = $request->validate(['title' => ['required', 'string', 'max:200'], 'description' => ['required', 'string', 'max:5000']]);
        $cr = $changes->submit(Auth::guard('client')->user(), $project, $data['title'], $data['description']);

        return redirect()->to(route('client.projects.show', $project).'#perubahan')->with('status', 'Permintaan perubahan '.$cr->number.' dihantar untuk penilaian.');
    }

    public function decideChange(Request $request, ChangeRequest $changeRequest, ChangeRequestService $changes): RedirectResponse
    {
        abort_unless((int) $changeRequest->customer_user_id === (int) Auth::guard('client')->id(), 404);
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'agree' => ['required_if:decision,approve', 'nullable', 'accepted']],
            ['agree.required_if' => 'Sila tanda persetujuan terhadap harga dan tempoh kerja tambahan.', 'agree.accepted' => 'Sila tanda persetujuan terhadap harga dan tempoh kerja tambahan.']);
        $cr = $changes->decide(Auth::guard('client')->user(), $changeRequest, $data['decision'] === 'approve', (string) $request->ip(), (string) $request->userAgent());

        if ($cr->status === ChangeRequest::APPROVED && $cr->invoice_id) {
            return redirect()->route('client.billing.invoice', $cr->invoice_id)->with('status', 'Kerja tambahan diluluskan. Sila bayar invois untuk meneruskan.');
        }

        return redirect()->to(route('client.projects.show', $cr->project_id).'#perubahan')->with('status', 'Keputusan anda disimpan.');
    }

    public function download(ProjectFile $file, ContentService $content): StreamedResponse
    {
        abort_unless($file->visibility === ProjectFile::CLIENT && (int) $file->project()->value('customer_user_id') === (int) Auth::guard('client')->id(), 404);

        return $content->download($file);
    }

    private function authorizeProject(Project $project): void
    {
        abort_unless((int) $project->customer_user_id === (int) Auth::guard('client')->id(), 404);
    }

    private function authorizeItem(ProjectContentItem $item): void
    {
        abort_unless((int) $item->project()->value('customer_user_id') === (int) Auth::guard('client')->id(), 404);
    }
}
