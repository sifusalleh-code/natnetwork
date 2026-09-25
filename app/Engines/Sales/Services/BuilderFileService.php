<?php

namespace App\Engines\Sales\Services;

use App\Engines\Sales\Models\BuilderFile;
use App\Engines\Sales\Models\BuilderSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BuilderFileService
{
    public const MAX_PER_KIND = 5;
    public const MAX_KB = 5120;
    public const MIMES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public function store(BuilderSession $session, string $kind, UploadedFile $file): BuilderFile
    {
        if ($session->files()->where('kind', $kind)->count() >= self::MAX_PER_KIND) {
            throw ValidationException::withMessages(['file' => ['Maksimum '.self::MAX_PER_KIND.' fail bagi setiap bahagian.']]);
        }
        $mime = (string) $file->getMimeType(); // dikesan daripada kandungan sebenar
        if (! in_array($mime, self::MIMES, true)) {
            throw ValidationException::withMessages(['file' => ['Jenis fail tidak dibenarkan. Gunakan JPG, PNG, WebP atau PDF.']]);
        }
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mime];
        $path = $file->storeAs('builder/'.$session->id, Str::random(40).'.'.$extension, 'local');
        $name = Str::limit(preg_replace('/[^\pL\pN ._()\-]+/u', '_', $file->getClientOriginalName()) ?: 'fail.'.$extension, 180, '');

        $record = $session->files()->create(['kind' => $kind, 'original_name' => $name, 'path' => $path, 'mime' => $mime, 'size' => (int) $file->getSize()]);
        $session->touch();

        return $record;
    }

    public function delete(BuilderFile $file): void
    {
        Storage::disk('local')->delete($file->path);
        $file->session?->touch();
        $file->delete();
    }

    public function toArray(BuilderFile $file): array
    {
        return ['id' => $file->id, 'kind' => $file->kind, 'name' => $file->original_name, 'size' => $file->size, 'is_image' => $file->isImage(), 'url' => route('builder.files.show', $file)];
    }
}
