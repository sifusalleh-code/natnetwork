<?php

namespace App\Engines\ProjectContent\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Models\Project;
use App\Engines\ProjectContent\Events\ContentItemChanged;
use App\Engines\ProjectContent\Models\ProjectContentItem;
use App\Engines\ProjectContent\Models\ProjectFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Project Content: keperluan, maklumat, fail berversi, semakan dan deliverable. Storan private; akses sentiasa disemak. */
class ContentService
{
    public const DISK = 'local';

    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public static function uploadRules(): array
    {
        return ['required', 'file', 'max:'.config('project_templates.upload_max_kb'), 'mimes:'.implode(',', config('project_templates.upload_extensions'))];
    }

    // ---------- Pelanggan ----------

    public function submitInfo(ProjectContentItem $item, string $text): ProjectContentItem
    {
        $this->assertClientCanSubmit($item);
        $item->forceFill(['info_text' => $text, 'status' => ProjectContentItem::SUBMITTED, 'submitted_at' => now(), 'help_requested' => false])->save();
        ContentItemChanged::dispatch($item, 'SUBMITTED');

        return $item;
    }

    public function submitFile(ProjectContentItem $item, UploadedFile $file): ProjectFile
    {
        $this->assertClientCanSubmit($item);

        return DB::transaction(function () use ($item, $file): ProjectFile {
            $stored = $this->store($item->project, $file, 'CLIENT', ProjectFile::CLIENT, false, $item->id);
            $item->forceFill(['status' => ProjectContentItem::SUBMITTED, 'submitted_at' => now(), 'help_requested' => false])->save();
            ContentItemChanged::dispatch($item, 'SUBMITTED');

            return $stored;
        });
    }

    public function requestHelp(ProjectContentItem $item): void
    {
        $this->assertClientCanSubmit($item);
        $item->forceFill(['help_requested' => true])->save();
        ContentItemChanged::dispatch($item, 'HELP');
    }

    // ---------- Admin ----------

    public function request(Admin $admin, Project $project, string $title, string $kind, bool $blocking, ?string $description): ProjectContentItem
    {
        $item = $project->contentItems()->create(['title' => $title, 'kind' => $kind, 'status' => ProjectContentItem::REQUESTED, 'blocking' => $blocking, 'description' => $description]);
        $this->audit->record('CONTENT_REQUESTED', $admin, $item, null, ['title' => $title, 'blocking' => $blocking]);
        ContentItemChanged::dispatch($item, 'REQUESTED');

        return $item;
    }

    public function review(Admin $admin, ProjectContentItem $item, bool $accept, ?string $reason): ProjectContentItem
    {
        if (! $accept && blank($reason)) {
            throw ValidationException::withMessages(['reason' => ['Sebab wajib diisi untuk "Perlu kemas kini".']]);
        }
        if ($item->status !== ProjectContentItem::SUBMITTED) {
            throw ValidationException::withMessages(['item' => ['Hanya item yang telah dihantar boleh disemak.']]);
        }

        $status = $accept ? ProjectContentItem::ACCEPTED : ProjectContentItem::NEEDS_UPDATE;
        $item->forceFill(['status' => $status, 'admin_note' => $accept ? null : $reason, 'reviewed_at' => now()])->save();
        $this->audit->record('CONTENT_REVIEWED', $admin, $item, null, ['status' => $status], $reason);
        ContentItemChanged::dispatch($item, $status);

        return $item;
    }

    public function adminUpload(Admin $admin, Project $project, UploadedFile $file, string $visibility, bool $deliverable): ProjectFile
    {
        $stored = $this->store($project, $file, 'ADMIN', $visibility, $deliverable && $visibility === ProjectFile::CLIENT, null);
        $this->audit->record('PROJECT_FILE_UPLOADED', $admin, $stored, null, ['visibility' => $visibility, 'deliverable' => $stored->is_deliverable]);

        return $stored;
    }

    // ---------- Akses ----------

    public function download(ProjectFile $file): StreamedResponse
    {
        abort_unless(Storage::disk(self::DISK)->exists($file->path), 404);

        return Storage::disk(self::DISK)->download($file->path, $file->original_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function store(Project $project, UploadedFile $file, string $by, string $visibility, bool $deliverable, ?int $itemId): ProjectFile
    {
        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        if (! in_array($ext, config('project_templates.upload_extensions'), true)) {
            throw ValidationException::withMessages(['file' => ['Jenis fail tidak dibenarkan.']]);
        }

        $version = $itemId ? ((int) ProjectFile::query()->where('content_item_id', $itemId)->max('version')) + 1 : 1;
        $path = $file->storeAs('projects/'.$project->id, Str::uuid()->toString().'.'.$ext, self::DISK);
        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            throw ValidationException::withMessages(['file' => ['Fail gagal disimpan. Sila cuba lagi.']]);
        }

        $safeName = Str::limit(preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($file->getClientOriginalName())) ?: 'fail.'.$ext, 180, '');

        return ProjectFile::query()->create([
            'project_id' => $project->id, 'content_item_id' => $itemId, 'uploaded_by' => $by, 'visibility' => $visibility,
            'is_deliverable' => $deliverable, 'version' => $version, 'path' => $path, 'original_name' => $safeName,
            'mime' => (string) $file->getMimeType(), 'size' => (int) $file->getSize(),
        ]);
    }

    private function assertClientCanSubmit(ProjectContentItem $item): void
    {
        if (! in_array($item->status, [ProjectContentItem::REQUESTED, ProjectContentItem::NEEDS_UPDATE, ProjectContentItem::SUBMITTED], true) || $item->project->isClosed()) {
            throw ValidationException::withMessages(['item' => ['Item ini tidak lagi boleh dikemas kini.']]);
        }
    }
}
