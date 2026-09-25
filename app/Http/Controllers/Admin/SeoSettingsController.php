<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Cms\Models\SeoPage;
use App\Engines\Cms\Models\SeoSetting;
use App\Engines\Cms\Services\SeoService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class SeoSettingsController extends Controller
{
    private const IMAGE_RULES = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,min_height=315'];

    public function show(SeoService $seo): View
    {
        $pages = collect(config('seo.pages'))->filter(fn ($p, $route) => Route::has($route))->map(fn ($page, $route) => [
            'route' => $route,
            'label' => $page['label'],
            'url' => route($route),
            'default_title' => $page['title'],
            'default_description' => $page['description'],
            'default_index' => (bool) $page['index'],
            'override' => $seo->overrides()->get($route),
            'indexable' => $seo->isIndexable($route),
        ]);

        return view('admin.seo.index', ['settings' => SeoSetting::current(), 'pages' => $pages, 'seo' => $seo]);
    }

    public function updateSettings(Request $request, SeoService $seo, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'default_description' => ['nullable', 'string', 'max:300'],
            'google_site_verification' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'bing_site_verification' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'default_og_image' => self::IMAGE_RULES,
            'remove_og_image' => ['nullable', 'boolean'],
        ], [
            'google_site_verification.regex' => 'Masukkan nilai content sahaja (tanpa tag <meta>).',
            'bing_site_verification.regex' => 'Masukkan nilai content sahaja (tanpa tag <meta>).',
        ]);

        $settings = SeoSetting::current();
        $previous = $settings->only(['site_name', 'default_description', 'default_og_image', 'google_site_verification', 'bing_site_verification']);
        $settings->fill([
            'site_name' => $data['site_name'],
            'default_description' => $data['default_description'] ?? null,
            'google_site_verification' => $data['google_site_verification'] ?? null,
            'bing_site_verification' => $data['bing_site_verification'] ?? null,
        ]);
        $oldImage = $settings->default_og_image;
        if ($request->hasFile('default_og_image')) {
            $settings->default_og_image = $seo->storeImage($request->file('default_og_image'));
        } elseif ($request->boolean('remove_og_image')) {
            $settings->default_og_image = null;
        }
        $settings->save();
        if ($oldImage !== $settings->default_og_image) {
            $seo->deleteImage($oldImage);
        }
        $seo->forget();
        $audit->record('SEO_SETTINGS_CHANGED', Auth::guard('admin')->user(), $settings, $previous, $settings->only(array_keys($previous)));

        return back()->with('status', 'Tetapan SEO umum disimpan.');
    }

    public function updatePage(Request $request, string $page, SeoService $seo, AuditLogger $audit): RedirectResponse
    {
        abort_unless((config('seo.pages')[$page] ?? null) && Route::has($page), 404);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:90'],
            'description' => ['nullable', 'string', 'max:300'],
            'index_mode' => ['required', 'in:default,index,noindex'],
            'og_image' => self::IMAGE_RULES,
            'remove_og_image' => ['nullable', 'boolean'],
        ]);

        $record = SeoPage::query()->firstOrNew(['route_name' => $page]);
        $previous = $record->exists ? $record->only(['title', 'description', 'og_image', 'noindex']) : null;
        $record->fill([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'noindex' => ['default' => null, 'index' => false, 'noindex' => true][$data['index_mode']],
        ]);
        $oldImage = $record->og_image;
        if ($request->hasFile('og_image')) {
            $record->og_image = $seo->storeImage($request->file('og_image'));
        } elseif ($request->boolean('remove_og_image')) {
            $record->og_image = null;
        }
        $record->save();
        if ($oldImage !== $record->og_image) {
            $seo->deleteImage($oldImage);
        }
        $seo->forget();
        $audit->record('SEO_PAGE_CHANGED', Auth::guard('admin')->user(), $record, $previous, $record->only(['route_name', 'title', 'description', 'og_image', 'noindex']));

        return redirect()->to(route('admin.seo').'#seo-'.str_replace('.', '-', $page))->with('status', 'SEO halaman '.config('seo.pages')[$page]['label'].' disimpan.');
    }
}
