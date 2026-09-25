<?php

namespace App\Http\Controllers;

use App\Engines\Cms\Services\SeoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SeoController extends Controller
{
    public function sitemap(SeoService $seo): Response
    {
        return response()
            ->view('seo.sitemap', ['entries' => $seo->sitemapEntries()])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function image(string $file): BinaryFileResponse
    {
        $path = SeoService::IMAGE_DIR.'/'.$file;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
