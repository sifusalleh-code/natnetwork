{{-- Status keseluruhan Start Project (Maklumat → Soal jawab → Master Specification → Quotation → Slot & Bayaran → Portal) --}}
@php($current = collect($journey['steps'])->first(fn ($s) => in_array($s['state'], ['current', 'failed', 'waiting'], true)))
<section class="bw-wrap bwj" aria-labelledby="bwj-title">
    <div class="bw-card bwj-card">
        <div class="bwj-head">
            <div>
                <p class="bw-kicker">Start Project anda</p>
                <h2 id="bwj-title">{{ $journey['order'] ? 'Projek anda telah disahkan' : ($current['label'] ?? 'Teruskan Start Project') }}</h2>
                @if ($current)<p class="bw-muted">{{ $current['detail'] }}</p>@endif
            </div>
            <div class="bwj-actions">
                @if ($current && ! empty($current['action']))
                    <a class="bw-btn bw-btn-primary" href="{{ $current['action'][1] }}">{{ $current['action'][0] }} @include('builder.partials.icon', ['name' => 'arrow-right'])</a>
                @endif
                @if ($journey['order'])
                    <form method="post" action="{{ route('builder.new') }}">@csrf<button class="bw-btn bw-btn-ghost" type="submit">Mula projek baharu</button></form>
                @endif
            </div>
        </div>
        <ol class="bwj-steps">
            @foreach ($journey['steps'] as $i => $step)
                <li class="is-{{ $step['state'] }}" @if ($step === $current) aria-current="step" @endif>
                    <span class="bwj-dot">@if ($step['state'] === 'done')@include('builder.partials.icon', ['name' => 'check'])@else{{ $i + 1 }}@endif</span>
                    <span class="bwj-text"><b>{{ $step['label'] }}</b><small>{{ $step['detail'] }}</small></span>
                </li>
            @endforeach
        </ol>
        @if ($journey['paymentFailed'] && $journey['canReset'])
            <form method="post" action="{{ route('builder.reset') }}" class="bwj-reset" x-data="{ ok: false }">
                @csrf
                <p><b>Bayaran belum berjaya.</b> Anda boleh teruskan bil yang sama (butang di atas), atau reset Start Project untuk mula semula. Reset membatalkan bil {{ $journey['invoice']?->number }}, quotation dan slot yang dipegang; rekod lama disimpan.</p>
                <label class="bwj-confirm"><input type="checkbox" name="confirm" value="1" x-model="ok"> Saya faham dan mahu reset Start Project.</label>
                @error('confirm')<span class="bw-error">{{ $message }}</span>@enderror
                @error('reset')<span class="bw-error">{{ $message }}</span>@enderror
                <button class="bw-btn bw-btn-ghost" type="submit" :disabled="! ok">Reset &amp; mula semula</button>
            </form>
        @endif
    </div>
</section>
