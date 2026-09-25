@extends('layouts.portal', ['title' => 'Quotations', 'active' => 'quotations'])

@section('content')
<header class="prt-top"><div><h1>Quotations</h1><p>Semak skop, harga dan terma, kemudian terima quotation.</p></div></header>
@if ($inReview > 0)<p class="notice">{{ $inReview }} quotation sedang disemak oleh pasukan kami dan akan dihantar kepada anda.</p>@endif
<article class="card prt-scroll">
    <table class="prt-table">
        <thead><tr><th>No.</th><th>Jumlah (RM)</th><th>Sah sehingga</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($quotations as $q)
            <tr>
                <td><b>{{ $q->number }}</b></td>
                <td>{{ number_format((float) $q->total_amount, 2) }}</td>
                <td>{{ $q->valid_until?->format('d/m/Y') }}</td>
                <td>@include('client.portal.partials.quotation-status', ['q' => $q])</td>
                <td><a href="{{ route('client.quotations.show', $q) }}">Lihat</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="prt-empty">Belum ada quotation. <a href="{{ route('builder.start') }}">Mula projek →</a></td></tr>
        @endforelse
        </tbody>
    </table>
</article>
@endsection
