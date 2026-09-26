@extends('layouts.public', ['title' => 'Daftar | NatNetwork Synergy'])

@section('content')
@php($partnershipSettings = \App\Engines\Partnership\Models\PartnerSetting::current())
@php($partnershipOpen = $partnershipSettings->program_enabled)
@php($partnershipFull = $partnershipOpen && $partnershipSettings->registrationFull())
@php($poolPercent = rtrim(rtrim(number_format((float) $partnershipSettings->pool_percent, 2), '0'), '.'))
@php($ic = [
    'shield-check' => '<path d="M12 2.5 4 5.5v6c0 5.1 3.4 9 8 10.5 4.6-1.5 8-5.4 8-10.5v-6l-8-3Z" fill="currentColor"/><path d="m8.5 12 2.5 2.5 4.5-5" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>',
    'gear' => '<path fill="currentColor" d="M19.4 13a7.7 7.7 0 0 0 0-2l2.1-1.6-2-3.5-2.5 1a7.4 7.4 0 0 0-1.7-1L15 3.3h-4l-.4 2.6a7.4 7.4 0 0 0-1.7 1l-2.5-1-2 3.5L6.6 11a7.7 7.7 0 0 0 0 2l-2.1 1.6 2 3.5 2.5-1a7.4 7.4 0 0 0 1.7 1l.4 2.6h4l.4-2.6a7.4 7.4 0 0 0 1.7-1l2.5 1 2-3.5ZM13 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z" transform="translate(-1 0)"/>',
    'bolt' => '<path fill="currentColor" d="M13.5 2 4.5 13.5h6L9.5 22l9-11.5h-6Z"/>',
    'users' => '<circle cx="9" cy="7.5" r="3.5" fill="currentColor"/><circle cx="17" cy="8.5" r="2.7" fill="currentColor"/><path fill="currentColor" d="M2 20a7 7 0 0 1 14 0v1H2Zm14.5 1v-1a8.4 8.4 0 0 0-2-5.4A5.5 5.5 0 0 1 22 20v1Z"/>',
    'cart' => '<path d="M3 4h2l2.3 10.2a1 1 0 0 0 1 .8h9a1 1 0 0 0 1-.8L20 8H6.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9.5" cy="19" r="1.5" fill="currentColor"/><circle cx="17" cy="19" r="1.5" fill="currentColor"/>',
    'handshake' => '<g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m11 17 2 2a1.4 1.4 0 0 0 2-2"/><path d="m14 14 2.5 2.5a1.4 1.4 0 0 0 2-2L15 11l-1.8 1.8a2 2 0 0 1-2.8-2.8L13.2 7.2a3 3 0 0 1 3.6-.4l.7.4a3 3 0 0 0 1.6.4H21v7.2l-2.5 1.4"/><path d="M3 7.6h2.4a3 3 0 0 0 1.6-.4l.4-.2M3 7.6v7.2l3.5 3.4a1.4 1.4 0 0 0 2-2"/></g>',
    'network' => '<g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="3"/><circle cx="6" cy="15" r="2.4"/><circle cx="18" cy="15" r="2.4"/><path d="M8 21c0-2.2 1.8-4 4-4s4 1.8 4 4M2 21.5c0-1.7 1.8-3 4-3M22 21.5c0-1.7-1.8-3-4-3"/></g>',
    'check' => '<circle cx="12" cy="12" r="10" fill="currentColor"/><path d="m7.5 12.2 3 3 6-6" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>',
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>',
    'lock' => '<rect x="5" y="10.5" width="14" height="11" rx="2.2" fill="currentColor"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" fill="none" stroke="currentColor" stroke-width="2.2"/><circle cx="12" cy="16" r="1.6" fill="#fff"/>',
    'headset' => '<path d="M4 13a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="2.2"/><rect x="3" y="12.5" width="4.5" height="7" rx="1.8" fill="currentColor"/><rect x="16.5" y="12.5" width="4.5" height="7" rx="1.8" fill="currentColor"/><path d="M19 19.5c0 1.4-1.6 2.3-4 2.3" fill="none" stroke="currentColor" stroke-width="1.8"/>',
    'doc' => '<path fill="currentColor" d="M6 2h8l5 5v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1Z"/><path d="M8.5 12h7M8.5 15.5h7M8.5 8.5h3" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/>',
])
@php($svg = fn (string $n) => '<svg viewBox="0 0 24 24" aria-hidden="true">'.$ic[$n].'</svg>')
<style>
    .rg { --rg-ink: #0b2447; --rg-muted: #4f6583; --rg-blue: #0b6fe0; --rg-blue2: #3a8ff5; --rg-green: #14a352; --rg-green2: #1fbf62; --rg-violet: #7a3be6; --rg-violet2: #9b5cf6; --rg-line: #dde9f6; position: relative; overflow: hidden; background: linear-gradient(180deg, #eef5fd 0%, #f6f9fe 55%, #fff 100%); }
    .rg *, .rg *::before, .rg *::after { box-sizing: border-box; }
    .rg-wrap { position: relative; width: min(100% - 2 * var(--z-gutter), 82rem); margin-inline: auto; }

    /* Hero */
    .rg-hero { position: relative; min-height: 20rem; padding: 1.9rem 0 3.4rem; }
    .rg-hero-photo { position: absolute; top: 0; right: calc(50% - 50vw); bottom: 0; width: min(52%, 46rem); z-index: 0; background: url('{{ asset('images/register/hero.webp') }}') left top / cover no-repeat; -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 20%), linear-gradient(180deg, #000 78%, transparent); -webkit-mask-composite: source-in; mask-image: linear-gradient(90deg, transparent 0, #000 20%), linear-gradient(180deg, #000 78%, transparent); mask-composite: intersect; }
    .rg-hero-copy { position: relative; z-index: 1; max-width: 52rem; }
    .rg-chip { display: inline-block; padding: .35rem .75rem; color: var(--rg-blue); background: #deecfc; border-radius: .5rem; font-size: .76rem; font-weight: 800; letter-spacing: .1em; }
    .rg-hero h1 { margin: .85rem 0 .7rem; color: var(--rg-ink); font-size: clamp(2.3rem, 5vw, 3.6rem); font-weight: 900; line-height: 1.02; letter-spacing: -.03em; }
    .rg-hero h1 span { display: block; color: var(--rg-blue); }
    .rg-lead { max-width: 37rem; margin: 0 0 1.4rem; color: var(--rg-muted); font-size: 1.08rem; line-height: 1.5; }
    .rg-perks { display: flex; flex-wrap: wrap; gap: 1rem 1.35rem; margin: 0; padding: 0; list-style: none; }
    .rg-perks li { display: flex; gap: .6rem; align-items: center; }
    .rg-perks svg { flex: none; width: 1.7rem; height: 1.7rem; color: var(--rg-blue); }
    @media (min-width: 72rem) { .rg-perks { flex-wrap: nowrap; } }
    .rg-perks b { display: block; color: var(--rg-ink); font-size: .88rem; line-height: 1.2; }
    .rg-perks small { color: var(--rg-muted); font-size: .76rem; }

    /* Panel pilihan */
    .rg-board { position: relative; z-index: 2; margin-top: -2.2rem; padding: 1.6rem 1.8rem 1.9rem; background: rgb(255 255 255 / .96); border: 1px solid var(--rg-line); border-radius: 1.2rem; box-shadow: 0 1.5rem 3.5rem rgb(11 36 71 / .08); }
    .rg-step { display: flex; gap: 1.1rem; align-items: center; margin-bottom: 1.3rem; }
    .rg-step > b { display: grid; flex: none; width: 3.3rem; height: 3.3rem; place-items: center; color: #fff; background: linear-gradient(135deg, var(--rg-blue2), var(--rg-blue)); border-radius: 50%; font-size: 1.35rem; box-shadow: 0 .6rem 1.2rem rgb(11 111 224 / .3); }
    .rg-step h2 { margin: 0; color: var(--rg-ink); font-size: 1.55rem; font-weight: 900; letter-spacing: -.02em; }
    .rg-step p { margin: .1rem 0 0; color: var(--rg-muted); }
    .rg-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 2rem; }
    .rg-card { --c1: var(--rg-blue); --c2: var(--rg-blue2); --tint: #f3f8ff; position: relative; display: flex; flex-direction: column; overflow: hidden; background: linear-gradient(180deg, var(--tint), #fff 70%); border: 1px solid color-mix(in srgb, var(--c1) 22%, #fff); border-radius: 1rem; box-shadow: 0 .8rem 1.8rem rgb(11 36 71 / .06); transition: transform .3s ease, box-shadow .3s ease; }
    .rg-card:hover { transform: translateY(-.35rem); box-shadow: 0 1.3rem 2.6rem color-mix(in srgb, var(--c1) 20%, transparent); }
    .rg-card.partnership { --c1: var(--rg-green); --c2: var(--rg-green2); --tint: #eefaf2; }
    .rg-card.affiliate { --c1: var(--rg-violet); --c2: var(--rg-violet2); --tint: #f6f1ff; }
    .rg-photo { display: block; width: 100%; height: 6rem; object-fit: cover; }
    .rg-card-head { display: flex; gap: .9rem; align-items: flex-start; padding: 0 1.1rem; margin-top: -2.4rem; }
    .rg-icon { display: grid; flex: none; width: 5rem; height: 5rem; place-items: center; color: #fff; background: radial-gradient(circle at 30% 25%, var(--c2), var(--c1)); border: 3px solid #fff; border-radius: 50%; box-shadow: 0 .7rem 1.4rem color-mix(in srgb, var(--c1) 35%, transparent); }
    .rg-icon svg { width: 2.4rem; height: 2.4rem; }
    .rg-card h3 { margin: 2.75rem 0 .15rem; color: var(--rg-ink); font-size: 1.45rem; font-weight: 900; letter-spacing: -.02em; }
    .rg-card-body { display: flex; flex: 1; flex-direction: column; padding: .2rem 1.1rem 1.1rem 6.8rem; }
    .rg-card-body > p { margin: 0 0 .8rem; color: var(--rg-muted); line-height: 1.4; }
    .rg-card ul { display: grid; gap: .45rem; margin: 0 0 1.2rem; padding: 0; list-style: none; }
    .rg-card li { display: flex; gap: .55rem; align-items: center; color: #22385a; font-size: .92rem; }
    .rg-card li svg { flex: none; width: 1.05rem; height: 1.05rem; color: var(--c1); }
    .rg-cta { margin: auto 1.1rem 1.1rem; }
    .rg-note { margin: 0 1.1rem .7rem; padding: .5rem .75rem; color: #7a5200; background: #fff6dc; border-radius: .55rem; font-size: .82rem; font-weight: 700; }
    .rg-note.rg-closed { color: #9f1c12; background: #fdecea; }
    .rg-btn { position: relative; display: flex; width: 100%; align-items: center; justify-content: center; gap: .6rem; padding: .95rem 1rem; overflow: hidden; color: #fff; font: inherit; font-size: 1rem; font-weight: 800; white-space: nowrap; background: linear-gradient(135deg, var(--c2), var(--c1)); border: 0; border-radius: .65rem; text-decoration: none; box-shadow: 0 .7rem 1.4rem color-mix(in srgb, var(--c1) 32%, transparent); transition: transform .2s ease, box-shadow .2s ease, filter .2s ease; }
    .rg-btn:hover { color: #fff; text-decoration: none; transform: translateY(-2px); filter: brightness(1.06); }
    .rg-btn svg { width: 1.1rem; height: 1.1rem; transition: transform .2s ease; }
    .rg-btn:hover svg { transform: translateX(.25rem); }
    .rg-btn[aria-disabled='true'] { color: #58738e; background: #e5eef6; box-shadow: none; cursor: not-allowed; transform: none; filter: none; }

    /* Jalur jaminan */
    .rg-trust { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 1.4rem 0 3rem; padding: 1.2rem .5rem; background: #fff; border: 1px solid var(--rg-line); border-radius: 1.1rem; box-shadow: 0 .8rem 2rem rgb(11 36 71 / .05); }
    .rg-trust > div { display: flex; gap: 1rem; align-items: center; padding: .2rem 1.3rem; }
    .rg-trust > div + div { border-left: 1px solid var(--rg-line); }
    .rg-trust i { display: grid; flex: none; width: 3.4rem; height: 3.4rem; place-items: center; color: var(--rg-blue); background: #e7f0fd; border-radius: 50%; }
    .rg-trust svg { width: 1.6rem; height: 1.6rem; }
    .rg-trust b { display: block; color: var(--rg-ink); font-size: .95rem; }
    .rg-trust span { display: block; margin-top: .2rem; color: var(--rg-muted); font-size: .84rem; line-height: 1.45; }

    .rg-reveal { opacity: 0; transform: translateY(1rem); transition: opacity .6s ease, transform .6s cubic-bezier(.2, .8, .2, 1); }
    .rg-reveal.is-in { opacity: 1; transform: none; }
    .rg-cards .rg-reveal:nth-child(2) { transition-delay: .08s; } .rg-cards .rg-reveal:nth-child(3) { transition-delay: .16s; }

    @media (max-width: 72rem) {
        .rg-cards { gap: 1.2rem; }
        .rg-card-body { padding-left: 1.1rem; }
        .rg-card h3 { margin-top: 2.6rem; }
        .rg-trust { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 1rem; }
        .rg-trust > div:nth-child(3) { border-left: 0; }
        .rg-btn { padding: .85rem .6rem; font-size: .9rem; white-space: normal; text-align: center; }
        .rg-hero { display: flex; flex-direction: column; padding-bottom: 0; }
        .rg-hero-photo { order: 2; flex: none; position: relative; right: auto; width: calc(100% + 2 * var(--z-gutter)); height: 15rem; margin: 1.5rem calc(-1 * var(--z-gutter)) 0; height: clamp(14rem, 40vw, 24rem); -webkit-mask-image: linear-gradient(180deg, transparent 0, #000 22%); mask-image: linear-gradient(180deg, transparent 0, #000 22%); -webkit-mask-composite: source-over; mask-composite: add; background-position: 30% 20%; }
        .rg-board { margin-top: -1.5rem; padding: 1.2rem; }
    }
    @media (max-width: 56rem) {
        .rg-cards { grid-template-columns: minmax(0, 1fr); }
        .rg-photo { height: 7rem; }
    }
    @media (max-width: 36rem) {
        .rg-perks { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem; }
        .rg-trust { grid-template-columns: minmax(0, 1fr); }
        .rg-trust > div + div { border-left: 0; border-top: 1px solid var(--rg-line); padding-top: 1rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .rg *, .rg *::before, .rg *::after { transition: none !important; }
        .rg-reveal { opacity: 1; transform: none; }
    }
</style>

<div class="rg">
    <div class="rg-wrap">
        <section class="rg-hero">
            <div class="rg-hero-photo" role="img" aria-label="Usahawan tersenyum bekerja dengan komputer riba di pejabat moden"></div>
            <div class="rg-hero-copy">
                <span class="rg-chip">PENDAFTARAN</span>
                <h1>Mulakan bersama <span>NatNetwork</span></h1>
                <p class="rg-lead">Pilih cara anda mahu menggunakan platform. Setiap peranan direka untuk memenuhi keperluan anda dengan mudah, selamat dan profesional.</p>
                <ul class="rg-perks">
                    <li>{!! $svg('shield-check') !!}<span><b>Daftar percuma</b><small>Proses cepat &amp; selamat</small></span></li>
                    <li>{!! $svg('gear') !!}<span><b>Selamat &amp; dipercayai</b><small>Teknologi terkini</small></span></li>
                    <li>{!! $svg('bolt') !!}<span><b>Mudah digunakan</b><small>Mesra pengguna</small></span></li>
                    <li>{!! $svg('users') !!}<span><b>Sokongan penuh</b><small>Kami sentiasa membantu.</small></span></li>
                </ul>
            </div>
        </section>

        <section class="rg-board" aria-labelledby="rg-pilih">
            <div class="rg-step"><b>1</b><div><h2 id="rg-pilih">Pilih jenis pendaftaran</h2><p>Klik pada pilihan yang sesuai dengan tujuan anda.</p></div></div>

            <div class="rg-cards">
                {{-- Client --}}
                <article class="rg-card client rg-reveal">
                    <img class="rg-photo" src="{{ asset('images/register/client.webp') }}" alt="" width="395" height="92" loading="lazy">
                    <div class="rg-card-head"><span class="rg-icon">{!! $svg('cart') !!}</span><h3>Buyer / Client</h3></div>
                    <div class="rg-card-body">
                        <p>Mulakan projek anda bersama kami.</p>
                        <ul>
                            <li>{!! $svg('check') !!}Dapatkan servis digital</li>
                            <li>{!! $svg('check') !!}Buat tempahan projek</li>
                            <li>{!! $svg('check') !!}Pantau status projek</li>
                            <li>{!! $svg('check') !!}Sokongan pelanggan</li>
                        </ul>
                    </div>
                    <div class="rg-cta"><a class="rg-btn" href="{{ route('builder.start') }}">Daftar sebagai Client {!! $svg('arrow') !!}</a></div>
                </article>

                {{-- Partnership --}}
                <article class="rg-card partnership rg-reveal">
                    <img class="rg-photo" src="{{ asset('images/register/partner.webp') }}" alt="" width="388" height="92" loading="lazy">
                    <div class="rg-card-head"><span class="rg-icon">{!! $svg('handshake') !!}</span><h3>Partnership</h3></div>
                    <div class="rg-card-body">
                        <p>Sertai program partnership dengan kami.</p>
                        <ul>
                            <li>{!! $svg('check') !!}Kerjasama jangka panjang</li>
                            <li>{!! $svg('check') !!}{{ $poolPercent }}% setiap jualan diagih</li>
                            <li>{!! $svg('check') !!}Agihan ikut nisbah modal</li>
                            <li>{!! $svg('check') !!}Potensi pendapatan lebih besar</li>
                        </ul>
                    </div>
                    @if ($partnershipFull)
                        <p class="rg-note rg-closed">Pendaftaran ditutup — modal terkumpul telah mencapai RM{{ number_format((float) $partnershipSettings->max_total_capital) }}.</p>
                        <div class="rg-cta"><span class="rg-btn" aria-disabled="true">Pendaftaran ditutup</span></div>
                    @elseif ($partnershipOpen)
                        <div class="rg-cta"><a class="rg-btn" href="{{ route('partner.register') }}">Daftar sebagai Partnership {!! $svg('arrow') !!}</a></div>
                    @else
                        <p class="rg-note">Pendaftaran Partnership belum diaktifkan.</p>
                        <div class="rg-cta"><span class="rg-btn" aria-disabled="true">Akan dibuka</span></div>
                    @endif
                </article>

                {{-- Affiliate --}}
                <article class="rg-card affiliate rg-reveal">
                    <img class="rg-photo" src="{{ asset('images/register/affiliate.webp') }}" alt="" width="396" height="92" loading="lazy">
                    <div class="rg-card-head"><span class="rg-icon">{!! $svg('network') !!}</span><h3>Affiliate</h3></div>
                    <div class="rg-card-body">
                        <p>Jana pendapatan melalui pemasaran affiliate.</p>
                        <ul>
                            <li>{!! $svg('check') !!}Promosi produk &amp; servis</li>
                            <li>{!! $svg('check') !!}Link affiliate unik</li>
                            <li>{!! $svg('check') !!}Jejaki klik dan pendaftaran</li>
                            <li>{!! $svg('check') !!}Komisen berterusan</li>
                        </ul>
                    </div>
                    <div class="rg-cta"><a class="rg-btn" href="{{ route('affiliate.register') }}">Daftar sebagai Affiliate {!! $svg('arrow') !!}</a></div>
                </article>
            </div>
        </section>

        <section class="rg-trust" aria-label="Jaminan pendaftaran">
            <div><i>{!! $svg('lock') !!}</i><div><b>Maklumat anda selamat bersama kami.</b><span>Kami menggunakan teknologi keselamatan terkini untuk melindungi data anda.</span></div></div>
            <div><i>{!! $svg('shield-check') !!}</i><div><b>Pengesahan OTP</b><span>Daftar dengan selamat dan mudah.</span></div></div>
            <div><i>{!! $svg('headset') !!}</i><div><b>Sokongan Pelanggan</b><span>Bantuan melalui portal setiap masa.</span></div></div>
            <div><i>{!! $svg('doc') !!}</i><div><b>Terma &amp; Syarat</b><span>Telus dan jelas untuk rujukan anda.</span></div></div>
        </section>
    </div>
</div>

<script>
(function () {
    var items = document.querySelectorAll('.rg-reveal');
    if ('IntersectionObserver' in window && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
        }, { threshold: .1 });
        items.forEach(function (el) { io.observe(el); });
    } else {
        items.forEach(function (el) { el.classList.add('is-in'); });
    }
})();
</script>
@endsection
