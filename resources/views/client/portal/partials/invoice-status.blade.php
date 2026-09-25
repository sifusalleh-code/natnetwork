@php($labels = ['ISSUED' => ['Belum dibayar', 'warn'], 'PARTIALLY_PAID' => ['Dibayar separa', 'warn'], 'PAID' => ['Dibayar', 'ok'], 'VOID' => ['Dibatalkan', 'bad']])
<span class="prt-badge {{ $labels[$inv->status][1] ?? '' }}">{{ $labels[$inv->status][0] ?? $inv->status }}</span>
@if ($inv->is_sandbox)<span class="prt-badge">UJIAN</span>@endif
