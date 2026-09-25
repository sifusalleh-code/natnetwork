@extends('layouts.affiliate')

@section('content')
<header class="aff-top"><div><h1>Studio Poster</h1><p>Pilih poster, salin caption (referral link disertakan) dan kongsi. Setiap download, salin atau share dikira sebagai perkongsian.</p></div></header>

@include('affiliate.partials.task', ['task' => $task])

@if ($posters->isEmpty())
    <article class="card"><p class="afx-empty">Belum ada poster. Admin akan menambah bahan promosi tidak lama lagi.</p></article>
@else
    <div class="afx-posters">
        @foreach ($posters as ['poster' => $poster, 'caption' => $caption])
            <article class="card afx-poster" x-data="posterCard({{ $poster->id }}, @js(route('affiliate.posters.share', $poster)))">
                <a class="afx-poster-img" href="{{ route('affiliate.posters.image', $poster) }}" target="_blank" rel="noopener"><img src="{{ route('affiliate.posters.image', $poster) }}" alt="Poster {{ $poster->title }}" loading="lazy"></a>
                <div class="afx-poster-body">
                    <h2>{{ $poster->title }}</h2>
                    <textarea readonly rows="5" x-ref="caption" aria-label="Caption {{ $poster->title }}">{{ $caption }}</textarea>
                    <div class="afx-actions">
                        <form method="post" action="{{ route('affiliate.posters.share', $poster) }}">@csrf<input type="hidden" name="channel" value="download"><button class="afx-act" type="submit" @click="bump()">Download</button></form>
                        <button class="afx-act" type="button" @click="copy()"><span x-text="copied ? 'Disalin ✓' : 'Salin caption'">Salin caption</span></button>
                        <form method="post" action="{{ route('affiliate.posters.share', $poster) }}" target="_blank">@csrf<input type="hidden" name="channel" value="whatsapp"><button class="afx-act is-wa" type="submit" @click="bump()">WhatsApp</button></form>
                        <form method="post" action="{{ route('affiliate.posters.share', $poster) }}" target="_blank">@csrf<input type="hidden" name="channel" value="facebook"><button class="afx-act is-fb" type="submit" @click="bump()">Facebook</button></form>
                        <form method="post" action="{{ route('affiliate.posters.share', $poster) }}" target="_blank">@csrf<input type="hidden" name="channel" value="telegram"><button class="afx-act is-tg" type="submit" @click="bump()">Telegram</button></form>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif

<script>
function posterCard(id, url) {
    return {
        copied: false,
        bump() { setTimeout(() => window.location.reload(), 1500); },
        async copy() {
            try { await navigator.clipboard.writeText(this.$refs.caption.value); } catch (e) { this.$refs.caption.select(); document.execCommand('copy'); }
            this.copied = true; setTimeout(() => this.copied = false, 2000);
            await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) }, body: JSON.stringify({ channel: 'copy' }), credentials: 'same-origin' });
        },
    };
}
</script>
@endsection
