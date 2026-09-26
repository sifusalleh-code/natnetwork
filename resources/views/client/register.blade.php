@extends('layouts.public', ['title' => 'Daftar | NatNetwork Synergy'])

@section('content')
@php($partnershipSettings = \App\Engines\Partnership\Models\PartnerSetting::current())
@php($partnershipOpen = $partnershipSettings->program_enabled)
@php($partnershipFull = $partnershipOpen && $partnershipSettings->registrationFull())
@php($poolPercent = rtrim(rtrim(number_format((float) $partnershipSettings->pool_percent, 2), '0'), '.'))
<style>
    .rg { --rg-blue: #0b66a7; --rg-blue2: #2a93d6; --rg-green: #15803d; --rg-green2: #22c55e; --rg-violet: #6d28d9; --rg-violet2: #8b5cf6; --rg-ink: #082a4d; --rg-muted: #58738e; --rg-line: #d6e8f6; position: relative; overflow: hidden; background: linear-gradient(180deg, #f3f9ff 0%, #fff 60%); }
    .rg *, .rg *::before, .rg *::after { box-sizing: border-box; }
    .rg-wrap { width: min(100% - 2 * var(--z-gutter), 76rem); margin-inline: auto; }
    .rg-blob { position: absolute; border-radius: 50%; filter: blur(60px); opacity: .55; pointer-events: none; animation: rg-float 14s ease-in-out infinite; }
    .rg-blob.b1 { width: 28rem; height: 28rem; top: -9rem; right: -6rem; background: #bfe0fb; }
    .rg-blob.b2 { width: 20rem; height: 20rem; top: 20rem; left: -8rem; background: #d9f7e4; animation-delay: -5s; }
    .rg-blob.b3 { width: 18rem; height: 18rem; bottom: 4rem; right: 18%; background: #ece3ff; animation-delay: -9s; }
    @keyframes rg-float { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(2rem, -1.5rem) scale(1.08); } }

    /* Hero */
    .rg-hero { position: relative; display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); gap: 2rem; align-items: center; padding: 2.5rem 0 2rem; }
    .rg-chip { display: inline-block; padding: .35rem .85rem; color: var(--rg-blue); background: #e3f1fc; border-radius: .6rem; font-size: .78rem; font-weight: 800; letter-spacing: .1em; }
    .rg-hero h1 { margin: .9rem 0 .6rem; color: var(--rg-ink); font-size: clamp(2.1rem, 5vw, 3.6rem); font-weight: 850; line-height: 1.05; letter-spacing: -.04em; }
    .rg-hero h1 span { background: linear-gradient(90deg, var(--rg-blue), var(--rg-blue2), var(--rg-blue)); background-size: 200% auto; -webkit-background-clip: text; background-clip: text; color: transparent; animation: rg-shine 5s linear infinite; }
    @keyframes rg-shine { to { background-position: 200% center; } }
    .rg-hero p.rg-lead { max-width: 40rem; margin: 0; color: var(--rg-muted); font-size: 1.12rem; }
    .rg-visual { position: relative; min-height: 16rem; }
    .rg-script { position: absolute; left: 0; top: 0; color: var(--rg-ink); font: italic 600 1.45rem/1.2 Georgia, 'Times New Roman', serif; transform: rotate(-8deg); opacity: .85; }
    .rg-script::after { content: ''; display: block; width: 9rem; height: .5rem; margin-top: .2rem; border-bottom: 2px solid var(--rg-ink); border-radius: 0 0 60% 40%; }
    .rg-checks { position: absolute; right: 10rem; top: 3.2rem; z-index: 2; display: grid; gap: .55rem; padding: 1rem 1.1rem; background: rgb(255 255 255 / .85); border: 1px solid var(--rg-line); border-radius: .9rem; box-shadow: 0 1rem 2rem rgb(8 42 77 / .08); backdrop-filter: blur(8px); animation: rg-bob 6s ease-in-out infinite; }
    .rg-checks span { display: flex; align-items: center; gap: .55rem; color: var(--rg-ink); font-size: .85rem; font-weight: 600; white-space: nowrap; }
    .rg-checks i { display: grid; width: 1.2rem; height: 1.2rem; place-items: center; color: #fff; background: var(--rg-blue); border-radius: 50%; font-style: normal; font-size: .7rem; }
    @keyframes rg-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-.5rem); } }
    .rg-laptop { position: absolute; right: 0; top: .5rem; width: 15rem; transform: perspective(900px) rotateY(-18deg) rotateX(6deg); transition: transform .6s ease; }
    .rg-visual:hover .rg-laptop { transform: perspective(900px) rotateY(-6deg) rotateX(2deg) scale(1.03); }
    .rg-screen { height: 9.5rem; padding: .7rem; background: linear-gradient(160deg, #0b3a6b, #0b66a7); border: .45rem solid #1b2533; border-radius: .7rem .7rem .2rem .2rem; box-shadow: 0 1.5rem 3rem rgb(8 42 77 / .3); overflow: hidden; }
    .rg-screen b { display: block; color: #fff; font-size: .7rem; letter-spacing: .04em; }
    .rg-bars { display: flex; align-items: flex-end; gap: .35rem; height: 5.2rem; margin-top: .5rem; }
    .rg-bars span { flex: 1; background: linear-gradient(180deg, #7fd0ff, #2a93d6); border-radius: .2rem .2rem 0 0; transform-origin: bottom; animation: rg-grow 2.4s ease-in-out infinite alternate; }
    .rg-bars span:nth-child(2) { animation-delay: .2s; } .rg-bars span:nth-child(3) { animation-delay: .4s; } .rg-bars span:nth-child(4) { animation-delay: .6s; } .rg-bars span:nth-child(5) { animation-delay: .8s; } .rg-bars span:nth-child(6) { animation-delay: 1s; }
    @keyframes rg-grow { from { transform: scaleY(.45); } to { transform: scaleY(1); } }
    .rg-base { height: .6rem; margin: 0 -1rem; background: linear-gradient(180deg, #cfd8e3, #9aa7b6); border-radius: 0 0 1rem 1rem; }

    /* Panel utama */
    .rg-board { position: relative; display: grid; grid-template-columns: minmax(0, 1fr) 22rem; gap: 1.5rem; padding: 1.5rem; margin-bottom: 1.25rem; background: rgb(255 255 255 / .92); border: 1px solid var(--rg-line); border-radius: 1.2rem; box-shadow: 0 1.5rem 3.5rem rgb(8 42 77 / .08); }
    .rg-step { display: flex; gap: 1rem; align-items: center; margin-bottom: 1.25rem; }
    .rg-step > b { display: grid; flex: none; width: 3.2rem; height: 3.2rem; place-items: center; color: #fff; background: linear-gradient(135deg, var(--rg-blue2), var(--rg-blue)); border-radius: 50%; font-size: 1.4rem; box-shadow: 0 .5rem 1rem rgb(11 102 167 / .3); }
    .rg-step h2 { margin: 0; color: var(--rg-ink); font-size: 1.45rem; font-weight: 800; letter-spacing: -.02em; }
    .rg-step p { margin: 0; color: var(--rg-muted); }
    .rg-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .rg-card { --c1: var(--rg-blue); --c2: var(--rg-blue2); --tint: #eef6fd; position: relative; display: flex; flex-direction: column; align-items: center; padding: 1.6rem 1.2rem 1.3rem; text-align: center; background: linear-gradient(180deg, var(--tint), #fff 55%); border: 1.5px solid var(--rg-line); border-radius: 1rem; cursor: pointer; isolation: isolate; transition: transform .35s cubic-bezier(.2, .8, .2, 1), box-shadow .35s ease, border-color .35s ease; transform-style: preserve-3d; outline: none; }
    .rg-card.partnership { --c1: var(--rg-green); --c2: var(--rg-green2); --tint: #effbf3; }
    .rg-card.affiliate { --c1: var(--rg-violet); --c2: var(--rg-violet2); --tint: #f4f0ff; }
    .rg-card::before { content: ''; position: absolute; inset: -1.5px; z-index: -1; border-radius: inherit; background: linear-gradient(135deg, var(--c2), var(--c1)); opacity: 0; transition: opacity .35s ease; }
    .rg-card:hover, .rg-card:focus-visible { transform: translateY(-.5rem); border-color: transparent; box-shadow: 0 1.4rem 2.6rem color-mix(in srgb, var(--c1) 22%, transparent); }
    .rg-card:hover::before, .rg-card:focus-visible::before, .rg-card.is-selected::before { opacity: 1; }
    .rg-card.is-selected { border-color: transparent; box-shadow: 0 1rem 2.2rem color-mix(in srgb, var(--c1) 26%, transparent); }
    .rg-card-inner { position: absolute; inset: 0; z-index: -1; border-radius: calc(1rem - 1.5px); background: linear-gradient(180deg, var(--tint), #fff 55%); }
    .rg-pop { position: absolute; top: -.8rem; right: 1rem; padding: .3rem .75rem; color: #fff; background: linear-gradient(90deg, #0b3a6b, var(--rg-blue)); border-radius: .6rem; font-size: .78rem; font-weight: 800; box-shadow: 0 .4rem .9rem rgb(8 42 77 / .25); }
    .rg-tick { position: absolute; top: .8rem; left: .8rem; display: grid; width: 1.6rem; height: 1.6rem; place-items: center; color: #fff; background: var(--c1); border-radius: 50%; font-size: .85rem; transform: scale(0); transition: transform .3s cubic-bezier(.3, 1.6, .5, 1); }
    .rg-card.is-selected .rg-tick { transform: scale(1); }
    .rg-icon { display: grid; width: 6rem; height: 6rem; place-items: center; background: radial-gradient(circle at 30% 25%, var(--c2), var(--c1)); border-radius: 50%; box-shadow: 0 .8rem 1.6rem color-mix(in srgb, var(--c1) 35%, transparent), inset 0 -.4rem .8rem rgb(0 0 0 / .15); transition: transform .5s cubic-bezier(.3, 1.4, .5, 1); }
    .rg-icon svg { width: 3rem; height: 3rem; fill: none; stroke: #fff; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .rg-card:hover .rg-icon { transform: translateY(-.3rem) rotate(-8deg) scale(1.08); }
    .rg-card h3 { margin: 1.1rem 0 .3rem; color: var(--rg-ink); font-size: 1.55rem; font-weight: 800; letter-spacing: -.02em; }
    .rg-card > p { min-height: 3rem; margin: 0 0 1rem; color: var(--rg-muted); }
    .rg-card ul { display: grid; gap: .55rem; width: 100%; padding: 0; margin: 0 0 1.3rem; list-style: none; text-align: left; }
    .rg-card li { display: flex; gap: .6rem; align-items: center; color: var(--rg-ink); font-size: .93rem; }
    .rg-card li i { display: grid; flex: none; width: 1.25rem; height: 1.25rem; place-items: center; color: #fff; background: var(--c1); border-radius: 50%; font-style: normal; font-size: .7rem; transition: transform .3s ease; }
    .rg-card:hover li i { transform: scale(1.12); }
    .rg-card:hover li:nth-child(2) i { transition-delay: .05s; } .rg-card:hover li:nth-child(3) i { transition-delay: .1s; } .rg-card:hover li:nth-child(4) i { transition-delay: .15s; }
    .rg-note { margin: -.6rem 0 .8rem; padding: .45rem .7rem; color: #7a5200; background: #fff6dc; border-radius: .5rem; font-size: .8rem; font-weight: 700; }
    .rg-note.rg-closed { color: #9f1c12; background: #fdecea; }

    .rg-btn { position: relative; display: inline-flex; width: 100%; margin-top: auto; align-items: center; justify-content: center; gap: .6rem; padding: .9rem .9rem; overflow: hidden; color: #fff; white-space: nowrap; font-size: .95rem; background: linear-gradient(135deg, var(--c2, var(--rg-blue2)), var(--c1, var(--rg-blue))); border: 0; border-radius: .6rem; font: inherit; font-weight: 800; cursor: pointer; text-decoration: none; box-shadow: 0 .6rem 1.2rem color-mix(in srgb, var(--c1, var(--rg-blue)) 30%, transparent); transition: transform .2s ease, box-shadow .2s ease, filter .2s ease; }
    .rg-btn::after { content: ''; position: absolute; top: 0; left: -120%; width: 60%; height: 100%; background: linear-gradient(100deg, transparent, rgb(255 255 255 / .45), transparent); transform: skewX(-20deg); transition: left .6s ease; }
    .rg-btn:hover { color: #fff; text-decoration: none; transform: translateY(-2px); filter: brightness(1.06); box-shadow: 0 .9rem 1.6rem color-mix(in srgb, var(--c1, var(--rg-blue)) 38%, transparent); }
    .rg-btn:hover::after { left: 130%; }
    .rg-btn:active { transform: translateY(0) scale(.97); }
    .rg-btn svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2.6; transition: transform .2s ease; }
    .rg-btn:hover svg { transform: translateX(.25rem); }
    .rg-btn[aria-disabled='true'] { color: #58738e; background: #e5eef6; box-shadow: none; cursor: not-allowed; transform: none; filter: none; }
    .rg-btn[aria-disabled='true']::after { display: none; }
    .rg-ripple { position: absolute; border-radius: 50%; background: rgb(255 255 255 / .55); transform: scale(0); animation: rg-ripple .65s ease-out forwards; pointer-events: none; }
    @keyframes rg-ripple { to { transform: scale(4); opacity: 0; } }

    /* Panel sisi */
    .rg-side { display: grid; align-content: start; gap: 1.2rem; padding-left: 1.5rem; border-left: 1px solid var(--rg-line); }
    .rg-help { padding: 1.1rem; background: linear-gradient(135deg, #eef6fd, #f7fbff); border: 1px solid var(--rg-line); border-radius: .9rem; }
    .rg-help h4 { display: flex; gap: .55rem; align-items: center; margin: 0 0 .35rem; color: var(--rg-ink); font-size: 1.05rem; font-weight: 800; }
    .rg-help p { margin: 0 0 .8rem; color: var(--rg-muted); font-size: .92rem; }
    .rg-bulb { animation: rg-glow 2.4s ease-in-out infinite; }
    @keyframes rg-glow { 0%, 100% { filter: drop-shadow(0 0 0 #facc15); } 50% { filter: drop-shadow(0 0 .45rem #facc15); } }
    .rg-compare-btn { position: relative; overflow: hidden; display: flex; width: 100%; justify-content: space-between; align-items: center; padding: .75rem .9rem; color: var(--rg-ink); background: #fff; border: 1px solid var(--rg-line); border-radius: .6rem; font: inherit; font-weight: 800; cursor: pointer; transition: border-color .2s, transform .2s, box-shadow .2s; }
    .rg-compare-btn:hover { border-color: var(--rg-blue2); transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgb(11 102 167 / .12); }
    .rg-compare-btn span { transition: transform .2s; } .rg-compare-btn:hover span { transform: translateX(.25rem); }
    .rg-detail { --c1: var(--rg-blue); }
    .rg-detail.partnership { --c1: var(--rg-green); } .rg-detail.affiliate { --c1: var(--rg-violet); }
    .rg-detail h4 { margin: 0 0 .15rem; color: var(--rg-ink); font-size: 1.1rem; font-weight: 800; }
    .rg-detail > small { display: block; margin-bottom: .8rem; color: var(--c1); font-weight: 800; letter-spacing: .06em; text-transform: uppercase; font-size: .72rem; }
    .rg-benefits { display: grid; padding: 0; margin: 0; list-style: none; }
    .rg-benefits li { display: flex; gap: .8rem; align-items: center; padding: .65rem 0; color: var(--rg-ink); font-size: .93rem; border-bottom: 1px solid var(--rg-line); transition: transform .2s ease, color .2s ease; }
    .rg-benefits li:hover { transform: translateX(.3rem); color: var(--c1); }
    .rg-benefits svg { flex: none; width: 1.3rem; height: 1.3rem; fill: none; stroke: var(--c1); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .rg-flow { display: grid; gap: .5rem; padding: 0; margin: .9rem 0 0; list-style: none; counter-reset: f; }
    .rg-flow li { position: relative; padding-left: 2rem; color: var(--rg-muted); font-size: .88rem; counter-increment: f; }
    .rg-flow li::before { content: counter(f); position: absolute; left: 0; top: .05rem; display: grid; width: 1.4rem; height: 1.4rem; place-items: center; color: #fff; background: var(--c1); border-radius: 50%; font-size: .72rem; font-weight: 800; }
    .rg-login { display: block; margin-top: .9rem; color: var(--c1); font-weight: 800; font-size: .92rem; }
    .rg-fade-enter { animation: rg-fade .35s ease both; }
    @keyframes rg-fade { from { opacity: 0; transform: translateY(.5rem); } to { opacity: 1; transform: none; } }

    /* Jalur jaminan */
    .rg-trust { display: grid; grid-template-columns: minmax(0, 1.3fr) repeat(3, minmax(0, 1fr)); gap: 1rem; align-items: center; padding: 1.1rem 1.4rem; margin-bottom: 3rem; background: #fff; border: 1px solid var(--rg-line); border-radius: 1rem; box-shadow: 0 .8rem 2rem rgb(8 42 77 / .05); }
    .rg-trust > div { display: flex; gap: .8rem; align-items: center; }
    .rg-trust > div:first-child { padding-right: 1rem; border-right: 1px solid var(--rg-line); }
    .rg-trust i { display: grid; flex: none; width: 3rem; height: 3rem; place-items: center; background: #eef6fd; border-radius: 50%; transition: transform .3s ease, background .3s ease; }
    .rg-trust > div:hover i { transform: scale(1.1) rotate(-6deg); background: #dceffd; }
    .rg-trust svg { width: 1.4rem; height: 1.4rem; fill: none; stroke: var(--rg-blue); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .rg-trust b { display: block; color: var(--rg-ink); font-size: .92rem; }
    .rg-trust span { color: var(--rg-muted); font-size: .82rem; }
    .rg-trust > div:first-child b { font-size: 1.05rem; }

    /* Modal perbandingan */
    .rg-modal { position: fixed; inset: 0; z-index: 60; display: grid; place-items: center; padding: 1rem; background: rgb(8 42 77 / .45); backdrop-filter: blur(4px); }
    .rg-modal-box { width: min(100%, 52rem); max-height: 90vh; overflow: auto; padding: 1.4rem; background: #fff; border-radius: 1rem; box-shadow: 0 2rem 4rem rgb(8 42 77 / .3); animation: rg-pop .3s cubic-bezier(.2, 1.3, .5, 1) both; }
    @keyframes rg-pop { from { opacity: 0; transform: scale(.94) translateY(1rem); } to { opacity: 1; transform: none; } }
    .rg-modal-box header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .rg-modal-box h3 { margin: 0; color: var(--rg-ink); font-weight: 800; }
    .rg-x { width: 2.2rem; height: 2.2rem; border: 0; border-radius: 50%; background: #eef6fd; color: var(--rg-ink); font-size: 1.2rem; cursor: pointer; transition: transform .2s, background .2s; }
    .rg-x:hover { transform: rotate(90deg); background: #dceffd; }
    .rg-table { width: 100%; border-collapse: collapse; font-size: .92rem; }
    .rg-table th, .rg-table td { padding: .7rem .6rem; border-bottom: 1px solid var(--rg-line); text-align: left; vertical-align: top; }
    .rg-table thead th { color: #fff; font-size: .85rem; }
    .rg-table thead th:nth-child(1) { background: transparent; }
    .rg-table thead th:nth-child(2) { background: var(--rg-blue); border-radius: .5rem .5rem 0 0; }
    .rg-table thead th:nth-child(3) { background: var(--rg-green); border-radius: .5rem .5rem 0 0; }
    .rg-table thead th:nth-child(4) { background: var(--rg-violet); border-radius: .5rem .5rem 0 0; }
    .rg-table tbody th { color: var(--rg-muted); font-weight: 700; }
    .rg-table tbody tr:hover td { background: #f7fbff; }

    /* Muncul ketika skrol */
    .rg-reveal { opacity: 0; transform: translateY(1.2rem); transition: opacity .7s ease, transform .7s cubic-bezier(.2, .8, .2, 1); }
    .rg-reveal.is-in { opacity: 1; transform: none; }
    .rg-cards .rg-reveal:nth-child(2) { transition-delay: .1s; } .rg-cards .rg-reveal:nth-child(3) { transition-delay: .2s; }
    [x-cloak] { display: none !important; }

    @media (max-width: 64rem) {
        .rg-board { grid-template-columns: 1fr; }
        .rg-side { padding-left: 0; border-left: 0; border-top: 1px solid var(--rg-line); padding-top: 1.2rem; grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr)); }
        .rg-trust { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .rg-trust > div:first-child { grid-column: 1 / -1; border-right: 0; padding-right: 0; }
    }
    @media (max-width: 52rem) {
        .rg-hero { grid-template-columns: 1fr; }
        .rg-visual { min-height: 13rem; }
        .rg-checks { right: auto; left: 0; top: 3.5rem; }
        .rg-visual { min-height: 14rem; }
        .rg-script { display: none; }
        .rg-cards { grid-template-columns: 1fr; }
        .rg-card > p { min-height: 0; }
    }
    @media (max-width: 30rem) {
        .rg-laptop { width: 11.5rem; }
        .rg-trust { grid-template-columns: 1fr; }
        .rg-board { padding: 1.1rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .rg *, .rg *::before, .rg *::after { animation: none !important; transition: none !important; }
        .rg-reveal { opacity: 1; transform: none; }
    }
</style>

<div class="rg" x-data="{ role: 'client', compare: false }" @keydown.escape.window="compare = false">
    <span class="rg-blob b1"></span><span class="rg-blob b2"></span><span class="rg-blob b3"></span>

    <div class="rg-wrap">
        <section class="rg-hero">
            <div class="rg-reveal">
                <span class="rg-chip">PENDAFTARAN</span>
                <h1>Mulakan bersama <span>NatNetwork</span></h1>
                <p class="rg-lead">Pilih cara anda mahu menggunakan platform. Setiap peranan direka untuk memenuhi keperluan anda dengan mudah, selamat dan profesional.</p>
            </div>
            <div class="rg-visual rg-reveal" aria-hidden="true">
                <div class="rg-script">Satu Platform<br>Pelbagai Peluang</div>
                <div class="rg-checks">
                    <span><i>✓</i>Daftar percuma</span>
                    <span><i>✓</i>Selamat &amp; dipercayai</span>
                    <span><i>✓</i>Proses mudah</span>
                    <span><i>✓</i>Sokongan penuh</span>
                </div>
                <div class="rg-laptop">
                    <div class="rg-screen"><b>NatNetwork · Dashboard</b><div class="rg-bars"><span style="height:35%"></span><span style="height:55%"></span><span style="height:45%"></span><span style="height:70%"></span><span style="height:62%"></span><span style="height:92%"></span></div></div>
                    <div class="rg-base"></div>
                </div>
            </div>
        </section>

        <section class="rg-board rg-reveal" aria-labelledby="rg-pilih">
            <div>
                <div class="rg-step"><b>1</b><div><h2 id="rg-pilih">Pilih jenis pendaftaran</h2><p>Klik pada pilihan yang sesuai dengan tujuan anda.</p></div></div>

                <div class="rg-cards" role="radiogroup" aria-label="Jenis pendaftaran">
                    {{-- Client --}}
                    <article class="rg-card client rg-reveal" role="radio" tabindex="0" :aria-checked="role === 'client'" :class="{ 'is-selected': role === 'client' }" @click="role = 'client'" @keydown.enter.prevent="role = 'client'" @keydown.space.prevent="role = 'client'" data-tilt>
                        <span class="rg-card-inner"></span>
                        <span class="rg-pop">Paling Popular</span>
                        <span class="rg-tick">✓</span>
                        <span class="rg-icon"><svg viewBox="0 0 24 24"><path d="M3 4h2l2.4 10.2a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6.2"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/></svg></span>
                        <h3>Buyer / Client</h3>
                        <p>Mulakan projek anda bersama kami.</p>
                        <ul>
                            <li><i>✓</i>Dapatkan servis digital</li>
                            <li><i>✓</i>Buat tempahan projek</li>
                            <li><i>✓</i>Pantau status projek</li>
                            <li><i>✓</i>Sokongan pelanggan</li>
                        </ul>
                        <a class="rg-btn" href="{{ route('builder.start') }}" data-ripple @click.stop>Daftar sebagai Client <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></a>
                    </article>

                    {{-- Partnership --}}
                    <article class="rg-card partnership rg-reveal" role="radio" tabindex="0" :aria-checked="role === 'partnership'" :class="{ 'is-selected': role === 'partnership' }" @click="role = 'partnership'" @keydown.enter.prevent="role = 'partnership'" @keydown.space.prevent="role = 'partnership'" data-tilt>
                        <span class="rg-card-inner"></span>
                        <span class="rg-tick">✓</span>
                        <span class="rg-icon"><svg viewBox="0 0 24 24"><path d="m11 17 2 2a1.4 1.4 0 0 0 2-2"/><path d="m14 14 2.5 2.5a1.4 1.4 0 0 0 2-2L15 11l-1.8 1.8a2 2 0 0 1-2.8-2.8L13.2 7.2a3 3 0 0 1 3.6-.4l.7.4a3 3 0 0 0 1.6.4H21v7.2l-2.5 1.4"/><path d="M3 7.6h2.4a3 3 0 0 0 1.6-.4l.4-.2"/><path d="M3 7.6v7.2l3.5 3.4a1.4 1.4 0 0 0 2-2"/></svg></span>
                        <h3>Partnership</h3>
                        <p>Sertai program partnership dengan kami.</p>
                        <ul>
                            <li><i>✓</i>Kerjasama jangka panjang</li>
                            <li><i>✓</i>{{ $poolPercent }}% setiap jualan diagih</li>
                            <li><i>✓</i>Agihan ikut nisbah modal</li>
                            <li><i>✓</i>Potensi pendapatan lebih besar</li>
                        </ul>
                        @if ($partnershipFull)
                            <p class="rg-note rg-closed">Pendaftaran ditutup — modal terkumpul telah mencapai RM{{ number_format((float) $partnershipSettings->max_total_capital) }}.</p>
                            <span class="rg-btn" aria-disabled="true">Pendaftaran ditutup</span>
                        @elseif ($partnershipOpen)
                            <a class="rg-btn" href="{{ route('partner.register') }}" data-ripple @click.stop>Daftar sebagai Partnership <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></a>
                        @else
                            <p class="rg-note">Pendaftaran Partnership belum diaktifkan.</p>
                            <span class="rg-btn" aria-disabled="true">Akan dibuka</span>
                        @endif
                    </article>

                    {{-- Affiliate --}}
                    <article class="rg-card affiliate rg-reveal" role="radio" tabindex="0" :aria-checked="role === 'affiliate'" :class="{ 'is-selected': role === 'affiliate' }" @click="role = 'affiliate'" @keydown.enter.prevent="role = 'affiliate'" @keydown.space.prevent="role = 'affiliate'" data-tilt>
                        <span class="rg-card-inner"></span>
                        <span class="rg-tick">✓</span>
                        <span class="rg-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="3"/><circle cx="6" cy="14.5" r="2.4"/><circle cx="18" cy="14.5" r="2.4"/><path d="M8 20c0-2.2 1.8-4 4-4s4 1.8 4 4M2 21c0-1.7 1.8-3 4-3M22 21c0-1.7-1.8-3-4-3"/></svg></span>
                        <h3>Affiliate</h3>
                        <p>Jana pendapatan melalui pemasaran affiliate.</p>
                        <ul>
                            <li><i>✓</i>Promosi produk &amp; servis</li>
                            <li><i>✓</i>Link affiliate unik</li>
                            <li><i>✓</i>Jejaki klik dan pendaftaran</li>
                            <li><i>✓</i>Komisen berterusan</li>
                        </ul>
                        <a class="rg-btn" href="{{ route('affiliate.register') }}" data-ripple @click.stop>Daftar sebagai Affiliate <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></a>
                    </article>
                </div>
            </div>

            <aside class="rg-side">
                <div class="rg-help">
                    <h4><svg class="rg-bulb" width="26" height="26" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2V17h5v-1.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3Z" fill="#fde68a" stroke="#d97706" stroke-width="1.6" stroke-linejoin="round"/></svg>Belum pasti pilihan yang sesuai?</h4>
                    <p>Anda boleh bermula sebagai Client dan kemudian menyertai program Partnership atau Affiliate pada bila-bila masa.</p>
                    <button type="button" class="rg-compare-btn" @click="compare = true" data-ripple>📊 &nbsp;Lihat perbandingan lengkap <span>›</span></button>
                </div>

                {{-- Kandungan ikut pilihan --}}
                <div class="rg-detail client rg-fade-enter" x-show="role === 'client'">
                    <small>Pilihan anda: Client</small>
                    <h4>Kelebihan bersama NatNetwork</h4>
                    <ul class="rg-benefits">
                        <li><svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6l-8-3Z"/></svg>Bayaran selamat melalui Billplz</li>
                        <li><svg viewBox="0 0 24 24"><path d="M4 5h16v11H4zM8 20h8M12 16v4"/></svg>Portal pelanggan untuk pantau projek</li>
                        <li><svg viewBox="0 0 24 24"><path d="M8 3v3M16 3v3M4 8h16v12H4z"/></svg>Pilih slot mula projek sendiri</li>
                        <li><svg viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 16 0v4a2 2 0 0 1-2 2h-1v-6h3M4 12v4a2 2 0 0 0 2 2h1v-6H4"/></svg>Sokongan selepas projek selesai</li>
                    </ul>
                    <ol class="rg-flow">
                        <li>Isi keperluan projek dalam Project Builder</li>
                        <li>Sahkan emel dengan kod OTP</li>
                        <li>Terima quotation &amp; bayar deposit 50%</li>
                    </ol>
                    <a class="rg-login" href="{{ route('client.login') }}">Sudah ada akaun? Log masuk Client →</a>
                </div>

                <div class="rg-detail partnership rg-fade-enter" x-show="role === 'partnership'" x-cloak>
                    <small>Pilihan anda: Partnership</small>
                    <h4>Kelebihan Program Partnership</h4>
                    <ul class="rg-benefits">
                        <li><svg viewBox="0 0 24 24"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg>{{ $poolPercent }}% daripada setiap jualan disahkan</li>
                        <li><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 4v8l6 3"/></svg>Agihan automatik ikut nisbah modal</li>
                        <li><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM4 10h16"/></svg>Penyata pulangan telus dalam portal</li>
                        <li><svg viewBox="0 0 24 24"><path d="M3 10h18M5 10v8M19 10v8M9 10v8M15 10v8M3 20h18M12 3 3 8h18l-9-5Z"/></svg>Pengeluaran terus ke akaun bank</li>
                    </ul>
                    <ol class="rg-flow">
                        <li>Daftar &amp; sahkan emel dengan OTP</li>
                        <li>Permohonan disemak oleh pasukan kami</li>
                        <li>Tambah modal (min. RM{{ number_format((float) $partnershipSettings->min_capital) }}) melalui Billplz</li>
                    </ol>
                    @if ($partnershipFull)
                        <p class="rg-note rg-closed" style="margin-top: 1rem;">Pendaftaran ditutup — modal terkumpul telah mencapai RM{{ number_format((float) $partnershipSettings->max_total_capital) }}.</p>
                        <a class="rg-login" href="{{ route('partner.login') }}">Sudah berdaftar? Log masuk Partnership →</a>
                    @elseif ($partnershipOpen)
                        <a class="rg-btn" style="--c1: var(--rg-green); --c2: var(--rg-green2); margin-top: 1rem;" href="{{ route('partner.register') }}" data-ripple>Daftar Partnership <svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></a>
                        <a class="rg-login" href="{{ route('partner.login') }}">Sudah berdaftar? Log masuk Partnership →</a>
                    @else
                        <p class="rg-note" style="margin-top: 1rem;">Pendaftaran akan dibuka tidak lama lagi.</p>
                    @endif
                </div>

                <div class="rg-detail affiliate rg-fade-enter" x-show="role === 'affiliate'" x-cloak>
                    <small>Pilihan anda: Affiliate</small>
                    <h4>Kelebihan Program Affiliate</h4>
                    <ul class="rg-benefits">
                        <li><svg viewBox="0 0 24 24"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>Link affiliate unik anda sendiri</li>
                        <li><svg viewBox="0 0 24 24"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg>Komisen bertingkat atas jumlah pelanggan</li>
                        <li><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M17 11l2 2 3-3"/></svg>Pelanggan dikaitkan kekal kepada anda</li>
                        <li><svg viewBox="0 0 24 24"><path d="M20 12v8H4v-8M2 7h20v5H2zM12 22V7M12 7H8a2 2 0 1 1 0-4c2 0 4 4 4 4Zm0 0h4a2 2 0 1 0 0-4c-2 0-4 4-4 4Z"/></svg>Studio poster untuk promosi</li>
                    </ul>
                    <ol class="rg-flow">
                        <li>Daftar dengan nama, emel &amp; telefon</li>
                        <li>Sahkan emel &amp; lengkapkan profil</li>
                        <li>Kongsi link dan jejaki komisen anda</li>
                    </ol>
                    <a class="rg-login" href="{{ route('affiliate.login') }}">Sudah berdaftar? Log masuk Affiliate →</a>
                </div>
            </aside>
        </section>

        <section class="rg-trust rg-reveal">
            <div><i><svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg></i><div><b>Maklumat anda selamat bersama kami.</b><span>Kami menggunakan teknologi keselamatan terkini untuk melindungi data anda.</span></div></div>
            <div><i><svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></i><div><b>Pengesahan OTP</b><span>Daftar dengan selamat</span></div></div>
            <div><i><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.4"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 20c0-2 1-3.6 3-4"/></svg></i><div><b>Sokongan Pelanggan</b><span>Bantuan melalui portal</span></div></div>
            <div><i><svg viewBox="0 0 24 24"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/></svg></i><div><b>Terma &amp; Syarat</b><span>Telus dan jelas</span></div></div>
        </section>
    </div>

    {{-- Perbandingan lengkap --}}
    <div class="rg-modal" x-show="compare" x-cloak x-transition.opacity @click.self="compare = false" role="dialog" aria-modal="true" aria-labelledby="rg-compare-title">
        <div class="rg-modal-box">
            <header><h3 id="rg-compare-title">Perbandingan jenis pendaftaran</h3><button type="button" class="rg-x" @click="compare = false" aria-label="Tutup">×</button></header>
            <div style="overflow-x: auto;">
                <table class="rg-table">
                    <thead><tr><th></th><th>Client</th><th>Partnership</th><th>Affiliate</th></tr></thead>
                    <tbody>
                        <tr><th>Sesuai untuk</th><td>Perniagaan yang mahukan website, sistem, AI atau integrasi</td><td>Individu / syarikat yang mahu berkongsi hasil jualan</td><td>Sesiapa yang mahu jana komisen melalui promosi</td></tr>
                        <tr><th>Maklumat daftar</th><td>Nama, telefon, emel</td><td>Nama, telefon, emel, IC / pendaftaran syarikat, bank</td><td>Nama, telefon, emel</td></tr>
                        <tr><th>Pengesahan</th><td>OTP emel</td><td>OTP emel + semakan pasukan</td><td>OTP emel + profil lengkap</td></tr>
                        <tr><th>Kos / modal</th><td>Ikut quotation (deposit 50%)</td><td>Modal min. RM{{ number_format((float) $partnershipSettings->min_capital) }}</td><td>Percuma</td></tr>
                        <tr><th>Pendapatan</th><td>—</td><td>{{ $poolPercent }}% setiap jualan, ikut nisbah modal</td><td>Komisen bertingkat setiap pelanggan</td></tr>
                        <tr><th>Portal</th><td>Projek, quotation, bil, fail, support</td><td>Modal, pulangan, penyata</td><td>Pelanggan, wallet, studio poster</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="button-row">
                <button type="button" class="rg-btn" style="width: auto;" @click="role = 'client'; compare = false" data-ripple>Pilih Client</button>
                <button type="button" class="rg-btn" style="width: auto; --c1: var(--rg-green); --c2: var(--rg-green2);" @click="role = 'partnership'; compare = false" data-ripple>Pilih Partnership</button>
                <button type="button" class="rg-btn" style="width: auto; --c1: var(--rg-violet); --c2: var(--rg-violet2);" @click="role = 'affiliate'; compare = false" data-ripple>Pilih Affiliate</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Muncul ketika skrol
    var items = document.querySelectorAll('.rg-reveal');
    if ('IntersectionObserver' in window && ! reduce) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
        }, { threshold: .12 });
        items.forEach(function (el) { io.observe(el); });
    } else {
        items.forEach(function (el) { el.classList.add('is-in'); });
    }

    // Kesan riak (ripple) semasa klik
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-ripple]');
        if (! btn || btn.getAttribute('aria-disabled') === 'true') return;
        var rect = btn.getBoundingClientRect(), size = Math.max(rect.width, rect.height);
        var r = document.createElement('span');
        r.className = 'rg-ripple';
        r.style.width = r.style.height = size + 'px';
        r.style.left = (e.clientX - rect.left - size / 2) + 'px';
        r.style.top = (e.clientY - rect.top - size / 2) + 'px';
        btn.appendChild(r);
        setTimeout(function () { r.remove(); }, 700);
    });

    // Condong 3D mengikut kedudukan tetikus
    if (! reduce && window.matchMedia('(hover: hover)').matches) {
        document.querySelectorAll('[data-tilt]').forEach(function (card) {
            card.addEventListener('mousemove', function (e) {
                var r = card.getBoundingClientRect();
                var x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
                card.style.transform = 'translateY(-.5rem) perspective(900px) rotateX(' + (-y * 6) + 'deg) rotateY(' + (x * 6) + 'deg)';
            });
            card.addEventListener('mouseleave', function () { card.style.transform = ''; });
        });
    }
})();
</script>
@endsection
