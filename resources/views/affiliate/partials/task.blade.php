{{-- Progress task mingguan (kelayakan withdrawal). --}}
@php($sharePct = $task['share_target'] > 0 ? min(100, round($task['shares'] / $task['share_target'] * 100)) : 100)
@php($clickPct = $task['click_target'] > 0 ? min(100, round($task['clicks'] / $task['click_target'] * 100)) : 100)
<article class="card afx-task">
    <div class="afx-task-head">
        <div><h2>Task mingguan</h2><p>{{ $task['start']->format('d/m') }} – {{ $task['end']->format('d/m/Y') }} · kelayakan withdrawal</p></div>
        <span @class(['afx-badge', 'is-ok' => $task['met'], 'is-wait' => ! $task['met']])>{{ $task['met'] ? 'Layak withdraw' : 'Belum lengkap' }}</span>
    </div>
    <div class="afx-progress">
        <div><p><span>Perkongsian</span><b>{{ $task['shares'] }} / {{ $task['share_target'] }}</b></p><div class="afx-bar" role="progressbar" aria-valuenow="{{ $sharePct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Perkongsian minggu ini"><i style="width: {{ $sharePct }}%"></i></div></div>
        <div><p><span>Unique clicks</span><b>{{ $task['clicks'] }} / {{ $task['click_target'] }}</b></p><div class="afx-bar" role="progressbar" aria-valuenow="{{ $clickPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Unique clicks minggu ini"><i style="width: {{ $clickPct }}%"></i></div></div>
    </div>
</article>
