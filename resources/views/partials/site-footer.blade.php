<footer class="z-footer">
    <div class="z-wrap">
        <div>
            <a class="z-logo" href="{{ route('home') }}" aria-label="NatNetwork Synergy — laman utama"><img class="z-logo-image" src="{{ asset('images/brand/natnetwork-synergy-primary-240.webp') }}" width="48" height="48" loading="lazy" alt="NatNetwork Synergy"><span class="z-logo-text"><b>{{ config('company.name') }}</b><small>Digital Technology Solutions</small></span></a>
            <p class="z-footer-about">NatNetwork Synergy membantu perniagaan membina website, sistem, automasi dan integrasi yang praktikal, jelas dan bersedia untuk digunakan.</p>
            <small style="display: block;">No. Reg: {{ config('company.registration_number') }}</small>
            <p class="z-footer-follow">Ikuti &#64;{{ config('company.socials.username') }}</p>
            <div class="z-socials">
                <a href="{{ config('company.socials.facebook') }}" target="_blank" rel="noopener" aria-label="Facebook NatNetwork Synergy"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg></a>
                <a href="{{ config('company.socials.instagram') }}" target="_blank" rel="noopener" aria-label="Instagram NatNetwork Synergy"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7.3a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4Zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0ZM16.9 2H7.1A5.1 5.1 0 0 0 2 7.1v9.8A5.1 5.1 0 0 0 7.1 22h9.8a5.1 5.1 0 0 0 5.1-5.1V7.1A5.1 5.1 0 0 0 16.9 2Zm3.4 14.9a3.4 3.4 0 0 1-3.4 3.4H7.1a3.4 3.4 0 0 1-3.4-3.4V7.1a3.4 3.4 0 0 1 3.4-3.4h9.8a3.4 3.4 0 0 1 3.4 3.4v9.8Z"/></svg></a>
                <a href="{{ config('company.socials.linkedin') }}" target="_blank" rel="noopener" aria-label="LinkedIn NatNetwork Synergy"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13ZM7.12 20.45H3.55V9h3.57v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/></svg></a>
            </div>
        </div>
        <div>
            <h2>Pautan</h2>
            <nav class="z-footer-links" aria-label="Pautan footer"><a href="{{ route('services') }}">Servis</a><a href="{{ route('opportunity') }}">Peluang</a><a href="{{ route('gallery.examples') }}">Projek</a><a href="{{ route('contact') }}">Hubungi</a></nav>
        </div>
        <div>
            <h2>Perkhidmatan</h2>
            @php($zFooterServices = \App\Engines\Pricing\Models\Service::query()->where('is_active', true)->orderBy('display_order')->get(['name', 'slug']))
            <nav class="z-footer-links" aria-label="Perkhidmatan">@foreach ($zFooterServices as $zService)<a href="{{ route('services') }}#servis-{{ $zService->slug }}">{{ $zService->name }}</a>@endforeach</nav>
        </div>
        <div>
            <h2>Hubungi</h2>
            <div class="z-footer-links"><a href="mailto:{{ config('company.email') }}">{{ config('company.email') }}</a><a href="tel:+6011167005857">{{ config('company.phone') }}</a></div>
        </div>
    </div>
    <div class="z-footer-bottom"><div class="z-wrap"><small>© {{ now()->year }} NatNetwork Synergy. Semua hak cipta terpelihara.</small><small>Digital Technology Solutions</small></div></div>
</footer>
