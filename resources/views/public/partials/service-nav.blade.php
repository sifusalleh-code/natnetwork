{{-- Navigasi kategori mendatar. Mobile: skrol mendatar. --}}
@php($catIcons = ['website-development' => 'website', 'e-commerce' => 'cart', 'custom-web-applications' => 'app', 'ai-automation' => 'ai', 'system-api-integration' => 'api', 'maintenance-support' => 'support'])
<nav class="svc-nav" aria-label="Kategori servis">
    <div class="z-wrap">
        <ul class="svc-nav-list">
            <li><a class="svc-chip is-active" href="#{{ $topAnchor }}" data-svc-link="all" aria-current="true">@include('public.partials.svc-icon', ['name' => 'grid'])Semua Servis</a></li>
            @foreach ($services as $service)
                <li><a class="svc-chip" href="#{{ $prefix }}{{ $service->slug }}" data-svc-link="{{ $service->slug }}">@include('public.partials.svc-icon', ['name' => $catIcons[$service->slug] ?? 'grid']){{ $service->name }}</a></li>
            @endforeach
        </ul>
    </div>
</nav>
