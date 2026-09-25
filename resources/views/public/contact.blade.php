@extends('layouts.public', ['title' => 'Hubungi Kami | NatNetwork Synergy'])

@php
    $ci = function (string $name) {
        $p = [
            'phone' => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
            'pin' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
            'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M3 21h18M8 7h4M8 11h4M8 15h4"/>',
            'tag' => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
            'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/>',
            'send' => '<path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/>',
            'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'badge' => '<path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6l-8-3Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
            'external' => '<path d="M14 4h6v6M20 4l-9 9M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
        ][$name] ?? '';

        return '<svg class="ct-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'.$p.'</svg>';
    };
    $lines = $company['address_lines'];
    $locality = collect(explode(',', preg_replace('/^\d{5}\s+/', '', end($lines))))->map(fn ($s) => \Illuminate\Support\Str::title(mb_strtolower(trim($s))))->implode(', ');
    $websiteLabel = rtrim(preg_replace('#^https?://#', '', $company['website']), '/');
@endphp

@section('content')
<div class="ct">
    <section class="ct-hero" aria-labelledby="ct-title">
        <div class="ct-hero-photo" style="background-image:url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&q=72&w=1200&h=700')" aria-hidden="true"></div>
        <div class="ct-wrap ct-hero-inner">
            <p class="ct-eyebrow">Hubungi kami</p>
            <h1 id="ct-title">Mari bincangkan apa yang anda mahu capai.</h1>
            <p class="ct-lead">Hantar pertanyaan kepada NatNetwork Synergy atau gunakan Guided Project Builder untuk menghantar keperluan projek secara tersusun.</p>
            <span class="ct-bar" aria-hidden="true"></span>
        </div>
    </section>

    <div class="ct-wrap">
        <ul class="ct-quick" aria-label="Maklumat hubungan">
            <li><span class="ct-quick-icon">{!! $ci('phone') !!}</span><span><small>Telefon</small><a href="tel:+6011167005857">{{ $company['phone'] }}</a></span></li>
            <li><span class="ct-quick-icon">{!! $ci('mail') !!}</span><span><small>Email</small><a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a></span></li>
            <li><span class="ct-quick-icon">{!! $ci('pin') !!}</span><span><small>Lokasi</small><b>{{ $locality }}</b></span></li>
            <li><span class="ct-quick-icon">{!! $ci('globe') !!}</span><span><small>Website</small><a href="{{ $company['website'] }}">{{ $websiteLabel }}</a></span></li>
        </ul>

        <div class="ct-main">
            <section class="ct-card ct-form-card" aria-labelledby="ct-form-title">
                <p class="ct-kicker">Borang pertanyaan</p>
                <h2 id="ct-form-title">Beritahu kami bagaimana kami boleh membantu.</h2>
                <p class="ct-sub">Kami akan membalas melalui email yang anda berikan.</p>
                <form method="post" action="{{ route('contact.send') }}" class="ct-form" x-data="{ n: {{ mb_strlen((string) old('message', '')) }} }">
                    @csrf
                    @error('contact_form')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="ct-grid">
                        <label class="ct-field" for="contact_name"><span class="ct-sr">Nama</span>{!! $ci('user') !!}<input id="contact_name" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Nama *" required></label>
                        <label class="ct-field" for="contact_company"><span class="ct-sr">Syarikat (pilihan)</span>{!! $ci('building') !!}<input id="contact_company" name="company" value="{{ old('company') }}" autocomplete="organization" placeholder="Syarikat (pilihan)"></label>
                        <label class="ct-field" for="contact_email"><span class="ct-sr">Email</span>{!! $ci('mail') !!}<input id="contact_email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="Email *" required></label>
                        <label class="ct-field" for="contact_phone"><span class="ct-sr">Telefon (pilihan)</span>{!! $ci('phone') !!}<input id="contact_phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="Telefon (pilihan)"></label>
                    </div>
                    <label class="ct-field" for="contact_subject"><span class="ct-sr">Subjek</span>{!! $ci('tag') !!}<input id="contact_subject" name="subject" value="{{ old('subject') }}" placeholder="Subjek *" required></label>
                    <label class="ct-field is-area" for="contact_message"><span class="ct-sr">Pertanyaan</span>{!! $ci('chat') !!}<textarea id="contact_message" name="message" rows="7" maxlength="5000" placeholder="Pertanyaan *" required @input="n = $event.target.value.length">{{ old('message') }}</textarea></label>
                    <p class="ct-count" aria-live="polite"><span x-text="n">{{ mb_strlen((string) old('message', '')) }}</span>/5000</p>
                    @foreach (['name', 'email', 'subject', 'message'] as $field) @error($field)<p class="form-error">{{ $message }}</p>@enderror @endforeach
                    <button class="ct-submit" type="submit">{!! $ci('send') !!} Hantar pertanyaan {!! $ci('arrow') !!}</button>
                </form>
            </section>

            <section class="ct-card ct-loc-card" aria-labelledby="ct-loc-title">
                <p class="ct-kicker">Lokasi</p>
                <h2 id="ct-loc-title">{{ $company['name'] }}</h2>
                <p class="ct-sub">No. Reg: {{ $company['registration_number'] }}</p>
                <div class="ct-map"><iframe src="{{ $mapEmbedUrl }}" title="Peta lokasi NatNetwork Synergy" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
                <div class="ct-info">
                    <div class="ct-info-item">
                        <span class="ct-quick-icon">{!! $ci('pin') !!}</span>
                        <div><h3>Lawati NatNetwork Synergy</h3><p>{!! implode('<br>', array_map('e', $lines)) !!}</p><a class="ct-link" href="{{ $mapUrl }}" target="_blank" rel="noopener">Buka alamat dalam peta <span aria-hidden="true">↗</span></a></div>
                    </div>
                    <div class="ct-info-item">
                        <span class="ct-quick-icon">{!! $ci('badge') !!}</span>
                        <div>
                            <h3>{{ $company['registration_certificate']['label'] }}</h3>
                            <p>Berdaftar dengan {{ $company['registration_certificate']['issuer'] }} di bawah Akta Pendaftaran Perniagaan 1956.</p>
                            <dl class="ct-dl"><div><dt>No. pendaftaran</dt><dd>{{ $company['registration_number'] }}</dd></div><div><dt>Sah sehingga</dt><dd>{{ $company['registration_certificate']['valid_until'] }}</dd></div></dl>
                            <a class="ct-link" href="{{ asset($company['registration_certificate']['document']) }}" target="_blank" rel="noopener">Lihat sijil pendaftaran {!! $ci('external') !!}</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section class="ct-social" aria-labelledby="ct-social-title">
            <p class="ct-kicker">Media sosial</p>
            <h2 id="ct-social-title">Ikuti &#64;{{ $company['socials']['username'] }}</h2>
            <div class="ct-social-links">
                <a href="{{ $company['socials']['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook NatNetwork Synergy"><svg class="ct-brand is-fb" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg><span>Facebook</span></a>
                <a href="{{ $company['socials']['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram NatNetwork Synergy"><svg class="ct-brand is-ig" viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="2.5" width="19" height="19" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.6" r="1.1" class="dot"/></svg><span>Instagram</span></a>
                <a href="{{ $company['socials']['linkedin'] }}" target="_blank" rel="noopener" aria-label="LinkedIn NatNetwork Synergy"><svg class="ct-brand is-in" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13ZM7.12 20.45H3.55V9h3.57v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/></svg><span>LinkedIn</span></a>
            </div>
        </section>

        <section class="ct-card ct-callout" aria-labelledby="ct-callout-title">
            <div><p class="ct-kicker">Untuk quotation yang tepat</p><h2 id="ct-callout-title">Gunakan Guided Project Builder.</h2><p class="ct-sub">Maklumat anda disimpan dalam sesi, kemudian dipautkan kepada Client Account selepas email disahkan.</p></div>
            <a class="ct-submit is-auto" href="{{ route('builder.start') }}">Ceritakan keperluan saya {!! $ci('arrow') !!}</a>
        </section>
    </div>
</div>
@endsection
