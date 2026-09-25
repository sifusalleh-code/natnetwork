@extends('layouts.admin', ['title' => 'Development Queue'])

@section('content')
<header class="adm-top"><div><h1>Development Queue</h1><p>Kapasiti: {{ $settings->max_active_projects }} projek serentak setiap minggu. HELD = pelanggan sedang membayar deposit (tamat selepas {{ $settings->hold_minutes }} minit).</p></div></header>

@if ($conflicts->isNotEmpty())
    <p class="adm-danger">{{ $conflicts->count() }} slot bertanda KONFLIK: deposit disahkan selepas hold tamat dan minggu tersebut sudah penuh. Hubungi pelanggan untuk jadual semula.</p>
@endif

<div class="adm-grid">
    <article class="card adm-scroll">
        <h2>Beban mingguan</h2>
        <table class="adm-table">
            <thead><tr><th>Minggu (Isnin)</th><th>Beban</th><th>Projek</th></tr></thead>
            <tbody>
            @foreach ($weeks as $w)
                <tr>
                    <td>{{ $w['start']->format('d/m/Y') }}</td>
                    <td><span @class(['adm-badge', 'FAILED' => $w['holds']->count() >= $settings->max_active_projects])>{{ $w['holds']->count() }} / {{ $settings->max_active_projects }}</span></td>
                    <td>@foreach ($w['holds'] as $h){{ $h->quotation?->number }} <span class="adm-help">({{ $h->status }})</span>@if (! $loop->last), @endif @endforeach</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </article>

    <article class="card">
        <h2>Tetapan jadual</h2>
        <form method="post" action="{{ route('admin.projects.queue.settings') }}" class="adm-form">
            @csrf @method('PUT')
            <label>Maksimum projek serentak<input name="max_active_projects" type="number" min="1" max="50" value="{{ old('max_active_projects', $settings->max_active_projects) }}" required></label>
            <label>Tempoh hold slot (minit)<input name="hold_minutes" type="number" min="5" max="1440" value="{{ old('hold_minutes', $settings->hold_minutes) }}" required></label>
            <label>Minggu dipaparkan kepada pelanggan<input name="weeks_ahead" type="number" min="2" max="52" value="{{ old('weeks_ahead', $settings->weeks_ahead) }}" required></label>
            <button class="button" type="submit">Simpan</button>
        </form>
    </article>
</div>

<article class="card adm-scroll" style="margin-top: 1rem;">
    <h2>Slot aktif</h2>
    <table class="adm-table">
        <thead><tr><th>Quotation</th><th>Pelanggan</th><th>Mula</th><th>Tamat</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($holds as $h)
            <tr>
                <td><a href="{{ route('admin.sales.quotation', $h->quotation_id) }}">{{ $h->quotation?->number }}</a></td>
                <td>{{ $h->quotation?->request?->customer?->name }}</td>
                <td>{{ $h->start_date->format('d/m/Y') }}</td>
                <td>{{ $h->endDate()->subDay()->format('d/m/Y') }} ({{ $h->weeks }} minggu)</td>
                <td><span class="adm-badge">{{ $h->status }}</span>@if ($h->status === 'HELD') <span class="adm-help">tamat {{ $h->expires_at?->format('H:i') }}</span>@endif @if ($h->conflict)<span class="adm-badge FAILED">KONFLIK</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5">Tiada slot aktif.</td></tr>
        @endforelse
        </tbody>
    </table>
</article>
@endsection
