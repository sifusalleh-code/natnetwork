<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Sales\Models\BuilderFile;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BuilderFileController extends Controller
{
    public function show(BuilderFile $file): Response
    {
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return response()->file(Storage::disk('local')->path($file->path), [
            'Content-Type' => $file->mime,
            'Content-Disposition' => ($file->isImage() ? 'inline' : 'attachment').'; filename="'.addcslashes($file->original_name, '"\\').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
