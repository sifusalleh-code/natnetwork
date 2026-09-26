@extends('layouts.public', ['title' => 'NatNetwork Synergy — Penyelesaian Digital untuk Bisnes Anda'])

@php
    $zCdn = fn (?string $id, int $w, int $h) => 'https://images.unsplash.com/'.$id.'?auto=format&fit=crop&q=72&w='.$w.'&h='.$h;
    $serviceIcons = ['z-wallet', 'z-shopping-cart', 'z-box', 'z-settings-2', 'z-layers', 'z-headset'];
    $serviceCopy = ['Website yang jelas, profesional dan direka untuk menukar pelawat kepada pelanggan.', 'Kedai dalam talian yang mudah diurus dan bersedia untuk berkembang.', 'Sistem web dibina mengikut cara operasi dan keperluan anda.', 'Automasi pintar untuk meningkatkan produktiviti dan mengurangkan kerja berulang.', 'Sambungkan sistem, API dan saluran komunikasi yang digunakan dalam perniagaan anda.', 'Sokongan berterusan untuk memastikan aset digital anda sentiasa berfungsi dengan baik.'];
    // Foto servis: ID foto sedia ada dalam config/builder.php (tiada foto baharu).
    $servicePhotos = [
        'website-development' => config('builder.photos.preview.website-development'),
        'e-commerce' => config('builder.photos.preview.e-commerce'),
        'custom-web-applications' => config('builder.photos.preview.custom-web-applications'),
        'ai-automation' => config('builder.photos.preview.ai-automation'),
        'system-api-integration' => config('builder.styles.technology'),
        'maintenance-support' => config('builder.photos.preview.people-photos'),
    ];
    $projects = [['furnihome', 'Website', 'FurniHome', 'Website e-dagang untuk jenama perabot tempatan.'], ['edumanage', 'System', 'EduManage', 'Sistem pengurusan pelajar untuk pusat pendidikan.'], ['bizflow', 'Automation', 'BizFlow', 'Automasi WhatsApp dan CRM untuk perniagaan perkhidmatan.']];
    // Pakej website sebenar daripada Pricing Engine. Ciri = 4 item pertama daripada ringkasan pakej (paparan sahaja).
    $websiteService = $services->firstWhere('slug', 'website-development');
    $packageIcons = ['landing-page' => 'z-send', 'starter-website' => 'z-globe', 'business-website' => 'z-store', 'corporate-website' => 'z-building'];
    $teaser = collect($websiteService?->packages ?? [])->whereIn('slug', array_keys($packageIcons))->values()->map(function ($package) {
        $main = trim(explode(';', rtrim((string) $package->summary, '.'), 2)[0]);
        $parts = array_map('trim', explode(', ', $main));
        $last = array_pop($parts);
        array_push($parts, ...array_map('trim', preg_split('/\s+dan\s+/u', (string) $last, 2)));

        return ['package' => $package, 'features' => array_map(fn ($f) => \Illuminate\Support\Str::ucfirst($f), array_slice(array_filter($parts), 0, 4))];
    });
@endphp

@section('content')
<style>
/* ============ Design tokens (front page) ============ */
.zip-home{background:#fff;color:var(--z-fg);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;font-size:1rem;line-height:1.65}
.zip-home *{box-sizing:border-box}
.zip-home a{text-decoration:none}
.zip-home h1,.zip-home h2,.zip-home h3{margin:0;color:var(--z-fg);text-wrap:balance}
.zip-home img{display:block;max-width:100%}
.z-sprite{position:absolute;width:0;height:0;overflow:hidden}
.zip-home .z-i{width:1.2rem;height:1.2rem;flex:none;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.z-eyebrow{margin:0;color:var(--z-primary);font-size:.76rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
main:has(.zip-home)+.z-footer{margin-top:0}

/* ============ Links ============ */
.z-text-link{display:inline-flex;align-items:center;gap:.45rem;color:var(--z-primary);font-size:.92rem;font-weight:700;transition:color .2s ease}
.z-text-link .z-icon{width:1rem;height:1rem;transition:transform .2s var(--z-ease)}
.z-text-link:hover{color:var(--z-primary-ink)}
.z-text-link:hover .z-icon{transform:translateX(4px)}

/* ============ Hero ============ */
.z-hero{position:relative;isolation:isolate;overflow:hidden;background:linear-gradient(180deg,#f3f9ff 0%,#fff 100%)}
.z-hero .z-wrap{display:grid;align-items:center;padding-block:clamp(2.75rem,7vw,4.5rem) clamp(2rem,5vw,3rem)}
.z-copy{max-width:41rem}
.z-hero h1{margin-top:1.1rem;font-size:clamp(2.1rem,3.4vw,2.95rem);text-wrap:pretty;font-weight:800;line-height:1.06;letter-spacing:-.035em}
.z-hero h1 span{display:block;margin-top:.12em;color:var(--z-primary)}
.z-lead{max-width:34rem;margin:1.4rem 0 0;color:var(--z-muted);font-size:1.12rem;line-height:1.7}
.z-actions{display:flex;flex-wrap:wrap;gap:.8rem;margin-top:2.1rem}
.z-actions .z-button{min-height:3.25rem;padding-inline:1.6rem}
.z-points{display:flex;flex-wrap:wrap;gap:.9rem 0;padding:0;margin:2.4rem 0 0;list-style:none}
.z-points li{display:inline-flex;align-items:center;gap:.55rem;padding-right:1rem;margin-right:1rem;white-space:nowrap;border-right:1px solid var(--z-border);color:#23405f;font-size:.88rem;font-weight:600}
.z-points li:last-child{padding-right:0;margin-right:0;border-right:0}
.z-points b{display:grid;width:2.2rem;height:2.2rem;place-items:center;color:var(--z-primary);background:#fff;border:1px solid var(--z-border);border-radius:50%;box-shadow:var(--z-shadow-sm)}
.z-points .z-icon{width:1rem;height:1rem}
.z-hero-media{margin-top:2.25rem;aspect-ratio:16/10;border-radius:var(--z-radius);background:#eaf4fc url('{{ asset('images/home/background-section-1-edge-1100.webp') }}') 88% center/cover no-repeat;box-shadow:var(--z-shadow-md);border:1px solid var(--z-border)}
@media (min-width:64.0625rem){
    .z-hero{min-height:min(calc(100svh - 4.5rem),44rem);display:flex;align-items:center;
        background-color:#eff8ff;background-image:linear-gradient(90deg,rgb(243 249 255/.98) 0%,rgb(243 249 255/.92) 32%,rgb(243 249 255/.4) 50%,rgb(243 249 255/0) 64%),url('{{ asset('images/home/background-section-1-edge.png') }}');
        background-image:linear-gradient(90deg,rgb(243 249 255/.98) 0%,rgb(243 249 255/.92) 32%,rgb(243 249 255/.4) 50%,rgb(243 249 255/0) 64%),image-set(url('{{ asset('images/home/background-section-1-edge.webp') }}') type('image/webp'),url('{{ asset('images/home/background-section-1-edge.png') }}') type('image/png'));
        background-position:center;background-size:cover;background-repeat:no-repeat}
    .z-hero::after{content:"";position:absolute;inset:auto 0 0;height:5rem;background:linear-gradient(180deg,rgb(255 255 255/0),#fff);z-index:-1;pointer-events:none}
    .z-hero .z-wrap{width:min(100% - 2 * var(--z-gutter),76rem)}
    .z-hero-media{display:none}
}

/* ============ Industri ============ */
.z-trust{background:#fff;border-bottom:1px solid var(--z-border)}
.z-trust .z-wrap{display:flex;align-items:center;gap:2rem;padding-block:1.5rem}
.z-trust-title{flex:none;max-width:12rem;margin:0;padding-right:2rem;border-right:1px solid var(--z-border);color:var(--z-muted);font-size:.72rem;font-weight:800;letter-spacing:.14em;line-height:1.5;text-transform:uppercase}
.z-industry{display:flex;flex-wrap:wrap;gap:.75rem 1.75rem;padding:0;margin:0;list-style:none}
.z-industry li{display:flex;align-items:center;gap:.6rem;color:#23405f;font-size:.9rem;font-weight:600}
.z-industry em{color:var(--z-muted);font-weight:500}
.z-industry i{display:grid;width:2.1rem;height:2.1rem;place-items:center;color:var(--z-primary);background:var(--z-secondary);border-radius:50%}
.z-industry i svg{width:1rem;height:1rem;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

/* ============ Sections ============ */
.z-section{padding-block:clamp(3.75rem,8vw,6.5rem)}
.z-soft{background:var(--z-soft);border-block:1px solid var(--z-border)}
.z-grid-section{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2.05fr);gap:clamp(2rem,4.5vw,4rem);align-items:start}
.z-section-copy{position:sticky;top:6.5rem;max-width:24rem}
.z-section-copy h2{text-wrap:pretty}
.z-section h2,.z-cta h2{margin-top:.9rem;font-size:clamp(1.8rem,2.6vw,2.3rem);font-weight:800;line-height:1.1;letter-spacing:-.03em}
.z-section-copy p:not(.z-eyebrow){margin:1.1rem 0 0;color:var(--z-muted)}
.z-section-copy .z-text-link{margin-top:1.6rem}

/* ============ Servis ============ */
.z-svc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.4rem}
.z-svc{display:flex;flex-direction:column;overflow:hidden;background:#fff;border:1px solid var(--z-border);border-radius:var(--z-radius);box-shadow:var(--z-shadow-sm);transition:transform .3s var(--z-ease),box-shadow .3s ease,border-color .3s ease}
.z-svc:hover,.z-svc:focus-within{transform:translateY(-4px);border-color:#b9dcf5;box-shadow:var(--z-shadow-md)}
.z-svc figure{margin:0;overflow:hidden;aspect-ratio:16/10;background:#e8f2fb}
.z-svc img{width:100%;height:100%;object-fit:cover;transition:transform .6s var(--z-ease)}
.z-svc:hover img{transform:scale(1.03)}
.z-svc-body{display:flex;flex:1;flex-direction:column;padding:0 1.35rem 1.35rem}
.z-svc-meta{display:flex;align-items:flex-end;gap:.6rem;margin-top:-1.5rem}
.z-svc-icon{position:relative;display:grid;width:3rem;height:3rem;place-items:center;color:var(--z-primary);background:#fff;border:1px solid var(--z-border);border-radius:.8rem;box-shadow:0 8px 18px rgb(8 42 77/.1)}
.z-svc-icon svg{width:1.3rem;height:1.3rem}
.z-svc-no{padding-bottom:.1rem;color:var(--z-primary-2);font-size:.78rem;font-weight:800;letter-spacing:.06em}
.z-svc h3{margin-top:1rem;font-size:1.12rem;font-weight:800;letter-spacing:-.01em}
.z-svc p{margin:.5rem 0 1.25rem;color:var(--z-muted);font-size:.92rem;line-height:1.6}
.z-svc .z-text-link{margin-top:auto}

/* ============ Projek ============ */
.z-proj-grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:1.4rem}
.z-proj{display:flex;flex-direction:column}
.z-proj.is-featured{grid-row:span 2}
.z-proj figure{position:relative;margin:0;overflow:hidden;aspect-ratio:16/10;background:#e8f2fb;border:1px solid var(--z-border);border-radius:var(--z-radius);box-shadow:var(--z-shadow-sm);transition:box-shadow .3s ease}
.z-proj.is-featured figure{flex:1;aspect-ratio:auto;min-height:20rem}
.z-proj img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .6s var(--z-ease)}
.z-proj:hover figure{box-shadow:var(--z-shadow-md)}
.z-proj:hover img{transform:scale(1.03)}
.z-proj small{display:block;margin-top:.95rem;color:var(--z-primary);font-size:.7rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
.z-proj h3{margin-top:.25rem;font-size:1.08rem;font-weight:800}
.z-proj.is-featured h3{font-size:1.3rem}
.z-proj p{margin:.3rem 0 0;color:var(--z-muted);font-size:.9rem;line-height:1.55}

/* ============ Proses ============ */
.z-steps{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1.5rem;padding:0;margin:0;list-style:none}
.z-steps li{position:relative}
.z-step-top{display:flex;align-items:center;gap:.75rem}
.z-step-icon{display:grid;width:3.6rem;height:3.6rem;place-items:center;color:var(--z-primary);background:var(--z-secondary);border:1px solid var(--z-border);border-radius:50%;transition:background-color .25s ease,color .25s ease,transform .3s var(--z-ease)}
.z-step-icon svg{width:1.45rem;height:1.45rem}
.z-steps li:hover .z-step-icon{color:#fff;background:var(--z-primary);transform:scale(1.05)}
.z-step-no{color:var(--z-primary);font-size:1.25rem;font-weight:800;letter-spacing:-.01em}
.z-step-arrow{position:absolute;top:1.3rem;right:-.3rem;width:1.1rem;height:1.1rem;color:#9fc3e2}
.z-steps h3{margin-top:1.2rem;font-size:1.04rem;font-weight:800}
.z-steps p{margin:.45rem 0 0;color:var(--z-muted);font-size:.92rem;line-height:1.6}

/* ============ Harga (teaser) ============ */
.z-price-section .z-grid-section{grid-template-columns:minmax(0,17rem) minmax(0,1fr)}
.z-price-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1.1rem}
.z-price{display:flex;flex-direction:column;padding:1.4rem 1.25rem 1.25rem;background:#fff;border:1px solid var(--z-border);border-radius:var(--z-radius);box-shadow:var(--z-shadow-sm);transition:transform .3s var(--z-ease),box-shadow .3s ease,border-color .3s ease}
.z-price:hover,.z-price:focus-within{transform:translateY(-4px);border-color:#b9dcf5;box-shadow:var(--z-shadow-md)}
.z-price-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}
.z-price h3{font-size:1.02rem;font-weight:800;line-height:1.3}
.z-price-icon{display:grid;flex:none;width:2.4rem;height:2.4rem;place-items:center;color:var(--z-primary);background:var(--z-secondary);border-radius:.65rem}
.z-price-icon svg{width:1.15rem;height:1.15rem}
.z-price-eta{display:flex;align-items:flex-start;gap:.45rem;margin:auto 0 1rem;padding-top:.9rem;border-top:1px solid var(--z-border);color:var(--z-muted);font-size:.78rem;line-height:1.45}
.z-price-eta .z-icon{width:.95rem;height:.95rem;margin-top:.1rem;color:var(--z-primary-2)}
.z-price strong{display:block;margin-top:1rem;color:var(--z-fg);font-size:1.7rem;font-weight:800;letter-spacing:-.03em;line-height:1.1}
.z-price ul{display:grid;gap:.5rem;padding:0;margin:1.1rem 0 1.25rem;list-style:none}
.z-price li{display:flex;align-items:flex-start;gap:.5rem;color:#23405f;font-size:.86rem;line-height:1.45;overflow-wrap:anywhere}
.z-price li .z-icon{width:1rem;height:1rem;margin-top:.1rem;color:#16a36a}
.z-price .z-button{width:100%;min-height:2.75rem;margin-top:auto;padding:.6rem 1rem;font-size:.9rem}

.z-price-eta+.z-button{margin-top:0}

/* ============ CTA ============ */
.z-cta{position:relative;isolation:isolate;overflow:hidden;color:#fff;background:#062f57}
.z-cta-photo{position:absolute;inset:0;z-index:-2;background:#062f57 center/cover no-repeat}
.z-cta::before{content:"";position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgb(4 32 62/.97) 0%,rgb(5 42 80/.9) 45%,rgb(6 52 98/.72) 100%)}
.z-cta .z-wrap{display:flex;align-items:center;justify-content:space-between;gap:2rem 3rem;padding-block:clamp(3.5rem,7vw,5rem)}
.z-cta .z-eyebrow{color:#9fd3f7}
.z-cta h2{max-width:36rem;color:#fff}
.z-cta p:not(.z-eyebrow){margin:.9rem 0 0;color:#cfe6f8;font-size:1.05rem}
.z-cta-actions{display:flex;flex-wrap:wrap;gap:.8rem;flex:none}
.z-cta .z-button{min-height:3.25rem;padding-inline:1.6rem;color:var(--z-fg);background:#fff;border-color:#fff;box-shadow:0 10px 24px rgb(0 0 0/.2)}
.z-cta .z-button:hover{color:var(--z-primary);background:#fff}
.z-cta .z-button.is-ghost{color:#fff;background:rgb(255 255 255/.06);border-color:rgb(255 255 255/.55);box-shadow:none}
.z-cta .z-button.is-ghost:hover{color:#fff;background:rgb(255 255 255/.14);border-color:#fff}

/* Muncul ketika skrol (hanya jika JS aktif) */
.zip-home.z-js .z-reveal{opacity:0;transform:translateY(18px);transition:opacity .6s ease,transform .6s var(--z-ease)}
.zip-home.z-js .z-reveal.is-in{opacity:1;transform:none}

/* ============ Responsive ============ */
@media (max-width:74.99rem){
    .z-grid-section,.z-price-section .z-grid-section{grid-template-columns:1fr}
    .z-section-copy{position:static;max-width:40rem}
    .z-price-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media (max-width:64rem){
    .z-svc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .z-steps{grid-template-columns:repeat(2,minmax(0,1fr));row-gap:2.25rem}
    .z-step-arrow{display:none}
}
@media (max-width:48rem){
    .z-trust .z-wrap{flex-direction:column;align-items:flex-start;gap:1rem}
    .z-trust-title{max-width:none;padding:0;border:0}
    .z-proj-grid{grid-template-columns:1fr}
    .z-proj.is-featured{grid-row:auto}
    .z-proj.is-featured figure{flex:none;aspect-ratio:4/3;min-height:0}
    .z-cta .z-wrap{flex-direction:column;align-items:flex-start}
    .z-cta-actions{width:100%}
    .z-cta-actions .z-button{flex:1 1 100%}
    .z-points li{padding-right:0;margin-right:1.25rem;border-right:0}
}
@media (max-width:40rem){
    .z-svc-grid,.z-price-grid{grid-template-columns:1fr}
    .z-steps{grid-template-columns:1fr;row-gap:0}
    .z-steps li{display:grid;grid-template-columns:3.6rem minmax(0,1fr);column-gap:1rem;padding-bottom:1.75rem}
    .z-steps li:not(:last-child)::before{content:"";position:absolute;top:3.9rem;bottom:.3rem;left:1.8rem;width:2px;background:var(--z-border)}
    .z-step-top{display:contents}
    .z-step-icon{grid-row:1 / span 3}
    .z-step-no{grid-column:2;font-size:.95rem;line-height:1.2;padding-top:.55rem}
    .z-steps h3{grid-column:2;margin-top:.2rem}
    .z-steps p{grid-column:2}
    .z-actions .z-button{flex:1 1 100%}
    .z-lead{font-size:1.03rem}
}
@media (prefers-reduced-motion:reduce){
    .zip-home *,.zip-home *::before,.zip-home *::after{transition:none!important;animation:none!important}
    .zip-home.z-js .z-reveal{opacity:1;transform:none}
}
</style>
<div class="zip-home">
    <svg class="z-sprite" aria-hidden="true" focusable="false">
        <symbol id="z-arrow-right" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></symbol>
        <symbol id="z-shield-check" viewBox="0 0 24 24"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></symbol>
        <symbol id="z-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
        <symbol id="z-bar-chart-3" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></symbol>
        <symbol id="z-store" viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M5 21V9"/><path d="M19 21V9"/><path d="m4 3 1 6h14l1-6Z"/><path d="M9 9v2a3 3 0 0 1-6 0V9"/><path d="M15 9v2a3 3 0 0 1-6 0V9"/><path d="M21 9v2a3 3 0 0 1-6 0V9"/></symbol>
        <symbol id="z-shopping-bag" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></symbol>
        <symbol id="z-graduation-cap" viewBox="0 0 24 24"><path d="M21.42 10.92a1 1 0 0 0-.42-.8L12.5 5.5a1 1 0 0 0-1 0L3 10.12a1 1 0 0 0 0 1.76l8.5 4.62a1 1 0 0 0 1 0L19 13v4"/><path d="M22 11v6"/><path d="M6 13.18v4.64a2 2 0 0 0 1.06 1.77l4 2.18a2 2 0 0 0 1.88 0l4-2.18A2 2 0 0 0 18 17.82v-4.64"/></symbol>
        <symbol id="z-heart-pulse" viewBox="0 0 24 24"><path d="M21 12c-1 0-3-1-3-1l-2 4-4-8-3 6H3"/><path d="M19.5 4.5A5.4 5.4 0 0 0 12 4a5.4 5.4 0 0 0-7.5.5C2.3 6.7 2 10 4 12.5l8 8 8-8c.7-.7 1.2-1.7 1.4-2.7"/></symbol>
        <symbol id="z-briefcase" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></symbol>
        <symbol id="z-wallet" viewBox="0 0 24 24"><path d="M20 7V6a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h15v8a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3V7"/><path d="M16 14h.01"/></symbol>
        <symbol id="z-shopping-cart" viewBox="0 0 24 24"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57L22 7H5.12"/></symbol>
        <symbol id="z-box" viewBox="0 0 24 24"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></symbol>
        <symbol id="z-settings-2" viewBox="0 0 24 24"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></symbol>
        <symbol id="z-layers" viewBox="0 0 24 24"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.84l8.57 3.9a2 2 0 0 0 1.66 0l8.57-3.9a1 1 0 0 0 0-1.84Z"/><path d="m22 12.5-9.17 4.17a2 2 0 0 1-1.66 0L2 12.5"/><path d="m22 17.5-9.17 4.17a2 2 0 0 1-1.66 0L2 17.5"/></symbol>
        <symbol id="z-headset" viewBox="0 0 24 24"><path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M21 14h-3a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2Z"/><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M18 21c0 1-2 2-4 2"/></symbol>
        <symbol id="z-globe" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></symbol>
        <symbol id="z-facebook" viewBox="0 0 24 24"><path fill="currentColor" stroke="none" d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></symbol>
        <symbol id="z-instagram" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.5" y2="6.5"/></symbol>
        <symbol id="z-linkedin" viewBox="0 0 24 24"><path fill="currentColor" stroke="none" d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29ZM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13ZM7.12 20.45H3.55V9h3.57v11.45ZM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0Z"/></symbol>
        <symbol id="z-send" viewBox="0 0 24 24"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/></symbol>
        <symbol id="z-building" viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10M3 21h18M8 7h4M8 11h4M8 15h4"/></symbol>
        <symbol id="z-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></symbol>
        <symbol id="z-lightbulb" viewBox="0 0 24 24"><path d="M9 18h6M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.1V17h6v-.2c0-.8.4-1.6 1-2.1A7 7 0 0 0 12 2Z"/></symbol>
        <symbol id="z-file-text" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></symbol>
        <symbol id="z-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
        <symbol id="z-code" viewBox="0 0 24 24"><path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/></symbol>
        <symbol id="z-rocket" viewBox="0 0 24 24"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09Z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2Z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></symbol>
    </svg>

    <section class="z-hero" aria-labelledby="z-hero-title">
        <div class="z-wrap">
            <div class="z-copy z-reveal">
                <p class="z-eyebrow">Digital Technology Solutions</p>
                <h1 id="z-hero-title">Anda fokus pada bisnes. <span>Kami bina penyelesaian digital yang menggerakkan anda lebih jauh.</span></h1>
                <p class="z-lead">NatNetwork Synergy membantu perniagaan membina website, sistem, automasi dan integrasi yang praktikal, jelas dan bersedia untuk digunakan.</p>
                <div class="z-actions">
                    <a class="z-button" href="{{ route('builder.start') }}">Mulakan projek <svg class="z-icon" aria-hidden="true"><use href="#z-arrow-right"/></svg></a>
                    <a class="z-button z-outline" href="#servis">Lihat servis</a>
                </div>
                <ul class="z-points" aria-label="Kelebihan kami">
                    <li><b aria-hidden="true"><svg class="z-icon"><use href="#z-shield-check"/></svg></b>Proses yang jelas</li>
                    <li><b aria-hidden="true"><svg class="z-icon"><use href="#z-users"/></svg></b>Sokongan berterusan</li>
                    <li><b aria-hidden="true"><svg class="z-icon"><use href="#z-bar-chart-3"/></svg></b>Fokus pada hasil</li>
                </ul>
            </div>
            <div class="z-hero-media" role="img" aria-label="Pasukan NatNetwork Synergy sedang membangunkan website dan sistem"></div>
        </div>
    </section>

    <section class="z-trust" aria-label="Industri pelanggan">
        <div class="z-wrap">
            <p class="z-trust-title">Dipercayai oleh perniagaan pelbagai industri</p>
            <ul class="z-industry">
                <li><i aria-hidden="true"><svg><use href="#z-store"/></svg></i>Perniagaan Kecil &amp; Sederhana</li>
                <li><i aria-hidden="true"><svg><use href="#z-shopping-bag"/></svg></i>E-Dagang</li>
                <li><i aria-hidden="true"><svg><use href="#z-graduation-cap"/></svg></i>Pendidikan</li>
                <li><i aria-hidden="true"><svg><use href="#z-heart-pulse"/></svg></i>Kesihatan</li>
                <li><i aria-hidden="true"><svg><use href="#z-briefcase"/></svg></i>Perkhidmatan Profesional</li>
                <li><em>dan banyak lagi</em></li>
            </ul>
        </div>
    </section>

    <section id="servis" class="z-section" aria-labelledby="z-servis-title">
        <div class="z-wrap z-grid-section">
            <div class="z-section-copy z-reveal">
                <p class="z-eyebrow">Bukan sekadar website</p>
                <h2 id="z-servis-title">Kami bina digital infrastruktur untuk bisnes anda.</h2>
                <p>Website untuk menarik pelanggan. Sistem untuk mengurus operasi. Automasi untuk mengurangkan kerja berulang.</p>
                <a class="z-text-link" href="{{ route('services') }}">Terokai servis kami <svg class="z-icon" aria-hidden="true"><use href="#z-arrow-right"/></svg></a>
            </div>
            <div class="z-svc-grid">
                @foreach ($services as $index => $service)
                    <article class="z-svc z-reveal">
                        <figure>
                            @if (! empty($servicePhotos[$service->slug]))
                                <img src="{{ $zCdn($servicePhotos[$service->slug], 640, 400) }}" srcset="{{ $zCdn($servicePhotos[$service->slug], 480, 300) }} 480w, {{ $zCdn($servicePhotos[$service->slug], 800, 500) }} 800w" sizes="(max-width: 40rem) 100vw, (max-width: 64rem) 50vw, 18rem" width="640" height="400" loading="lazy" decoding="async" alt="">
                            @endif
                        </figure>
                        <div class="z-svc-body">
                            <div class="z-svc-meta">
                                <span class="z-svc-icon" aria-hidden="true"><svg class="z-icon"><use href="#{{ $serviceIcons[$index] ?? 'z-box' }}"/></svg></span>
                                <span class="z-svc-no" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <h3>{{ $service->name }}</h3>
                            <p>{{ $serviceCopy[$index] ?? '' }}</p>
                            <a class="z-text-link" href="{{ route('services') }}#servis-{{ $service->slug }}" aria-label="Lihat servis {{ $service->name }}">Lihat servis <svg class="z-icon" aria-hidden="true"><use href="#z-arrow-right"/></svg></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="projek" class="z-section z-soft" aria-labelledby="z-projek-title">
        <div class="z-wrap z-grid-section">
            <div class="z-section-copy z-reveal">
                <p class="z-eyebrow">Projek terpilih</p>
                <h2 id="z-projek-title">Penyelesaian sebenar. Untuk hasil sebenar.</h2>
                <p>Beberapa contoh projek yang telah kami bina untuk pelanggan kami.</p>
                <a class="z-text-link" href="{{ route('gallery.examples') }}">Lihat lebih banyak projek <svg class="z-icon" aria-hidden="true"><use href="#z-arrow-right"/></svg></a>
            </div>
            <div class="z-proj-grid">
                @foreach ($projects as $i => [$slug, $kind, $name, $desc])
                    <article class="z-proj z-reveal{{ $i === 0 ? ' is-featured' : '' }}">
                        <figure>
                            <picture>
                                <source srcset="{{ asset('images/home/project-'.$slug.'-640.webp') }}" type="image/webp">
                                <img src="{{ asset('images/home/project-'.$slug.'.png') }}" width="640" height="640" loading="lazy" decoding="async" alt="Pratonton projek {{ $name }}">
                            </picture>
                        </figure>
                        <small>{{ $kind }}</small>
                        <h3>{{ $name }}</h3>
                        <p>{{ $desc }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="tentang" class="z-section" aria-labelledby="z-proses-title">
        <div class="z-wrap z-grid-section">
            <div class="z-section-copy z-reveal">
                <p class="z-eyebrow">Cara kami bekerja</p>
                <h2 id="z-proses-title">Dari idea hingga pelaksanaan.</h2>
                <p>Proses yang teratur untuk memastikan projek anda berjalan lancar.</p>
            </div>
            <ol class="z-steps">
                @foreach ([['z-lightbulb', 'Ceritakan matlamat', 'Kami faham keperluan dan objektif anda.'], ['z-file-text', 'Semak skop', 'Kami cadangkan solusi dan anggaran yang jelas.'], ['z-code', 'Pembangunan', 'Projek dimulakan mengikut pelan yang dipersetujui.'], ['z-rocket', 'Pelancaran & sokongan', 'Dilancarkan dan kami terus sokong anda selepas itu.']] as $i => [$icon, $title, $desc])
                    <li class="z-reveal">
                        <div class="z-step-top">
                            <span class="z-step-icon" aria-hidden="true"><svg class="z-icon"><use href="#{{ $icon }}"/></svg></span>
                            <span class="z-step-no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        @if (! $loop->last)<svg class="z-icon z-step-arrow" aria-hidden="true"><use href="#z-arrow-right"/></svg>@endif
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="harga" class="z-section z-soft z-price-section" aria-labelledby="z-harga-title">
        <div class="z-wrap z-grid-section">
            <div class="z-section-copy z-reveal">
                <p class="z-eyebrow">Harga yang jelas</p>
                <h2 id="z-harga-title">Mulakan dengan skop. Bukan teka-teki.</h2>
                <p>Harga permulaan berdasarkan jenis projek.</p>
                <a class="z-text-link" href="{{ route('services') }}">Lihat semua harga <svg class="z-icon" aria-hidden="true"><use href="#z-arrow-right"/></svg></a>
            </div>
            <div class="z-price-grid">
                @foreach ($teaser as ['package' => $package, 'features' => $features])
                    <article class="z-price z-reveal">
                        <div class="z-price-head">
                            <h3>{{ $package->name }}</h3>
                            <span class="z-price-icon" aria-hidden="true"><svg class="z-icon"><use href="#{{ $packageIcons[$package->slug] }}"/></svg></span>
                        </div>
                        <strong>{{ $package->price_label }}</strong>
                        @if ($features)
                            <ul aria-label="Skop {{ $package->name }}">
                                @foreach ($features as $feature)<li><svg class="z-icon" aria-hidden="true"><use href="#z-check"/></svg><span>{{ $feature }}</span></li>@endforeach
                            </ul>
                        @endif
                        @if ($package->delivery_estimate)<p class="z-price-eta"><svg class="z-icon" aria-hidden="true"><use href="#z-clock"/></svg><span>Anggaran pembangunan aktif: {{ $package->delivery_estimate }}</span></p>@endif
                        <a class="z-button z-outline" href="{{ route('services') }}#servis-{{ $websiteService->slug }}" aria-label="Lihat pakej {{ $package->name }}">Lihat pakej <svg class="z-icon" aria-hidden="true"><use href="#z-arrow-right"/></svg></a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="hubungi" class="z-cta" aria-labelledby="z-cta-title">
        <div class="z-cta-photo" style="background-image:url('{{ $zCdn(config('builder.photos.entry_guided'), 1600, 600) }}')" aria-hidden="true"></div>
        <div class="z-wrap z-reveal">
            <div>
                <p class="z-eyebrow">Ada projek dalam fikiran?</p>
                <h2 id="z-cta-title">Mari jadikan ia realiti.</h2>
                <p>Bincang dengan kami tanpa komitmen.</p>
            </div>
            <div class="z-cta-actions">
                <a class="z-button" href="{{ route('builder.start') }}">Mulakan Projek <svg class="z-icon" aria-hidden="true"><use href="#z-arrow-right"/></svg></a>
                <a class="z-button is-ghost" href="mailto:{{ config('company.email') }}">Hubungi kami</a>
            </div>
        </div>
    </section>
</div>

<script>
(function () {
    var root = document.querySelector('.zip-home');
    if (! root || ! ('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    root.classList.add('z-js');
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    root.querySelectorAll('.z-reveal').forEach(function (el) { io.observe(el); });
})();
</script>
@endsection
