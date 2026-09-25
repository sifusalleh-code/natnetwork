@php
    $rm = fn (int $cents) => 'RM '.number_format($cents / 100, 2);
    $locked = $plans['locked'];
    $choice = old('plan', $plans['selected'] ?? 'DEPOSIT');
    $reward = $plans['full']['reward'];
@endphp
<fieldset class="pp-plans" @if ($locked) disabled @endif>
    <legend>Cara bayaran</legend>
    @if ($locked)
        <p class="prt-empty">Cara bayaran telah dipilih untuk invois {{ $locked->invoice?->number }}. Untuk menukar, reset Start Project di halaman Mula Projek.</p>
    @endif
    <div class="pp-grid">
        <label @class(['pp-card', 'is-disabled' => $locked && $locked->plan !== 'DEPOSIT'])>
            <input type="radio" name="plan" value="DEPOSIT" @checked(($locked?->plan ?? $choice) === 'DEPOSIT')>
            <span class="pp-body">
                <b>Bayar {{ $plans['deposit']['percent'] }}% sekarang</b>
                <strong>{{ $rm($plans['deposit']['pay_cents']) }}</strong>
                <small>Baki {{ $rm($plans['deposit']['balance_cents']) }} diinvois apabila projek mencapai 80% siap.</small>
            </span>
        </label>
        <label @class(['pp-card', 'is-best', 'is-disabled' => $locked && $locked->plan !== 'FULL'])>
            <input type="radio" name="plan" value="FULL" @checked(($locked?->plan ?? $choice) === 'FULL')>
            <span class="pp-body">
                <b>Bayar penuh 100%</b>
                <strong>{{ $rm($plans['full']['pay_cents']) }} @if ($plans['full']['discount_cents'] > 0)<s>{{ $rm($plans['full']['gross_cents']) }}</s>@endif</strong>
                <small>Tiada invois baki.</small>
                @if ($reward)<span class="pp-reward">Ganjaran: {{ $reward['label'] }}@if (! empty($reward['addon_value'])) (bernilai {{ $reward['addon_value'] }})@endif</span>@endif
            </span>
        </label>
    </div>
    @error('plan')<p class="form-error">{{ $message }}</p>@enderror
</fieldset>
