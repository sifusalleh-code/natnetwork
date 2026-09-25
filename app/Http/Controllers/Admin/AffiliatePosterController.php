<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Affiliate\Models\AffiliatePoster;
use App\Engines\Affiliate\Models\AffiliateShare;
use App\Engines\Affiliate\Services\AffiliatePosterService;
use App\Engines\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AffiliatePosterController extends Controller
{
    private const IMAGE_RULES = ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    public function index(): View
    {
        return view('admin.affiliate.posters', [
            'posters' => AffiliatePoster::query()->orderBy('display_order')->latest('id')->get(),
            'shareCounts' => AffiliateShare::query()->selectRaw('affiliate_poster_id, count(*) as total')->groupBy('affiliate_poster_id')->pluck('total', 'affiliate_poster_id'),
        ]);
    }

    public function store(Request $request, AffiliatePosterService $posters, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'caption' => ['required', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'image' => ['required', ...self::IMAGE_RULES],
        ]);
        $image = $posters->storeImage($request->file('image'));
        $poster = AffiliatePoster::query()->create([
            'title' => $data['title'], 'caption' => $data['caption'], 'display_order' => $data['display_order'] ?? 0,
            'image_path' => $image['path'], 'image_mime' => $image['mime'], 'is_active' => true, 'created_by_admin_id' => Auth::guard('admin')->id(),
        ]);
        $audit->record('AFFILIATE_POSTER_CREATED', Auth::guard('admin')->user(), $poster, null, ['title' => $poster->title]);

        return back()->with('status', 'Poster "'.$poster->title.'" ditambah.');
    }

    public function update(Request $request, AffiliatePoster $poster, AffiliatePosterService $posters, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'caption' => ['required', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', ...self::IMAGE_RULES],
        ]);
        $previous = $poster->only(['title', 'caption', 'display_order', 'is_active', 'image_path']);
        $poster->fill(['title' => $data['title'], 'caption' => $data['caption'], 'display_order' => $data['display_order'] ?? 0, 'is_active' => $request->boolean('is_active')]);
        if ($request->hasFile('image')) {
            $old = $poster->image_path;
            $image = $posters->storeImage($request->file('image'));
            $poster->fill(['image_path' => $image['path'], 'image_mime' => $image['mime']]);
            $poster->save();
            $posters->deleteImage($old);
        } else {
            $poster->save();
        }
        $audit->record('AFFILIATE_POSTER_UPDATED', Auth::guard('admin')->user(), $poster, $previous, $poster->only(array_keys($previous)));

        return back()->with('status', 'Poster "'.$poster->title.'" dikemas kini.');
    }

    public function image(AffiliatePoster $poster): Response
    {
        abort_unless(Storage::disk('local')->exists($poster->image_path), 404);

        return response()->file(Storage::disk('local')->path($poster->image_path), ['Content-Type' => $poster->image_mime, 'X-Content-Type-Options' => 'nosniff']);
    }
}
