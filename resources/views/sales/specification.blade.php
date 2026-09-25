@extends('layouts.public', ['title' => 'Master Specification | NatNetwork Synergy'])

@section('content')
<section class="page-intro"><p class="eyebrow">Master Specification</p><h1>Semak keperluan projek anda.</h1><p class="lead">Dokumen ini dibina daripada jawapan anda. Edit melalui Builder jika ada perkara yang perlu diubah.</p></section>
<section class="section specification-shell">
    @if (session('specification_status'))<p class="notice">{{ session('specification_status') }}</p>@endif
    @error('specification')<p class="form-error">{{ $message }}</p>@enderror
    @if (! $specification)
        <article class="card"><h2>Belum ada draf</h2><p>Jana draf berdasarkan keperluan Builder yang telah disimpan.</p><form method="post" action="{{ route('specification.generate') }}">@csrf<button class="button" type="submit">Jana Master Specification Draft</button></form></article>
    @else
        <article class="card specification-card"><div class="status-line"><p class="eyebrow">Versi {{ $specification->version }}</p><strong>{{ $specification->status }}</strong></div><h2>Ringkasan projek</h2>
            @if ($specification->specification_snapshot['selected_package'])<p><strong>Pakej:</strong> {{ $specification->specification_snapshot['selected_package']['name'] }} · {{ $specification->specification_snapshot['selected_package']['price_label'] }}</p>@endif
            @php($cost = $specification->specification_snapshot['cost_estimate'] ?? null)
            @if ($cost)
                <div class="spec-cost">
                    <h3>Kos projek</h3>
                    <ul>@foreach ($cost['lines'] as $l)<li><span>{{ $l['description'] }}</span><b>{{ $l['label'] }}</b></li>@endforeach</ul>
                    <p class="spec-cost-total"><span>Jumlah</span><strong>RM{{ number_format($cost['total_cents'] / 100, 2) }}</strong></p>
                    <p class="adm-help">{{ $cost['needs_review'] ? 'Pakej ini memerlukan semakan pasukan kami; quotation akan dihantar selepas semakan.' : 'Selepas diluluskan, quotation dengan jumlah ini dijana terus untuk anda terima dan bayar.' }}</p>
                </div>
            @endif
            <dl class="spec-list">@foreach ($specification->specification_snapshot['project_summary'] as $item)<div><dt>{{ $item['label'] }}</dt><dd>{{ is_array($item['value']) ? implode(', ', $item['value']) : $item['value'] }}</dd></div>@endforeach @foreach (collect($specification->specification_snapshot['files'] ?? [])->groupBy('kind_label') as $kindLabel => $kindFiles)<div><dt>Fail {{ $kindLabel }}</dt><dd>{{ $kindFiles->pluck('name')->implode(', ') }}</dd></div>@endforeach</dl>
            @if ($specification->status === 'DRAFT')<div class="spec-actions"><a href="{{ route('builder.start') }}">Edit keperluan dalam Builder</a><form method="post" action="{{ route('specification.approve', $specification) }}">@csrf<button class="button" type="submit">Sahkan Master Specification</button></form></div>@else<p class="notice">Diluluskan pada {{ $specification->approved_at?->format('d/m/Y H:i') }}. Versi ini dikunci.</p>@endif
        </article>
    @endif
</section>
@endsection
