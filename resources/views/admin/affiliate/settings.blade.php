@extends('layouts.admin', ['title' => 'Tetapan Affiliate'])

@section('content')
<header class="adm-top"><div><h1>Tetapan Affiliate</h1><p>Kadar komisyen bertingkat dan tempoh cookie.</p></div></header>

<div class="adm-grid">
    <article class="card">
        <h2>Kadar komisyen bertingkat</h2>
        <p class="adm-help">Setiap bahagian jumlah terkumpul pelanggan dikira dengan kadar tingkatnya, kemudian dicampur. Tingkat terakhir tiada had atas. Versi aktif: #{{ $version->id }} (sejak {{ $version->effective_from->format('d/m/Y H:i') }}).</p>
        <form method="post" action="{{ route('admin.affiliate.settings.rates') }}" class="adm-form" id="rates-form">
            @csrf @method('PUT')
            <div class="adm-scroll">
                <table class="adm-table" id="tier-table">
                    <thead><tr><th>#</th><th>Hingga (RM)</th><th>Kadar (%)</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($tiers as $i => $tier)
                        <tr>
                            <td class="tier-no">{{ $loop->iteration }}</td>
                            <td><input name="tiers[{{ $i }}][up_to]" type="number" step="0.01" min="0.01" value="{{ $tier['up_to'] ?? '' }}" placeholder="Tiada had" class="tier-upto"></td>
                            <td><input name="tiers[{{ $i }}][rate]" type="number" step="0.01" min="0.01" max="100" value="{{ $tier['rate'] }}" required></td>
                            <td><button type="button" class="button button-secondary button-compact tier-remove">Buang</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="button button-secondary" id="tier-add">+ Tambah tingkat</button>
            <div class="adm-danger">
                <b>Amaran:</b> kadar baharu hanya terpakai untuk komisyen <b>akan datang</b>. Komisyen yang telah dikira kekal. Jumlah terkumpul pelanggan bersambung (tidak reset) — contohnya pelanggan dengan RM3,000 terkumpul yang membeli RM2,000 lagi akan dikira bahagian RM3,000.01–RM5,000 mengikut jadual baharu.
            </div>
            <label class="check"><input type="checkbox" name="confirm" value="1" required> Saya faham dan mahu menyimpan kadar baharu.</label>
            <button class="button" type="submit">Simpan kadar</button>
        </form>
    </article>

    <article class="card">
        <h2>Contoh (jadual aktif)</h2>
        <table class="adm-table">
            <thead><tr><th>Jika pelanggan beli</th><th>Affiliate dapat</th><th>Kadar efektif</th></tr></thead>
            <tbody>@foreach ($examples as $ex)<tr><td>RM{{ number_format($ex['sale']) }}</td><td>RM{{ number_format($ex['commission'], 2) }}</td><td>{{ number_format($ex['commission'] / $ex['sale'] * 100, 2) }}%</td></tr>@endforeach</tbody>
        </table>
    </article>

    <article class="card">
        <h2>Tempoh cookie</h2>
        <form method="post" action="{{ route('admin.affiliate.settings.cookie') }}" class="adm-form">
            @csrf @method('PUT')
            <label>Tempoh (hari)<input name="cookie_days" type="number" min="1" max="365" value="{{ old('cookie_days', $cookieDays) }}" required></label>
            <span class="adm-help">Tempoh pelawat yang klik link affiliate boleh dikaitkan dengan affiliate tersebut. Terpakai untuk klik baharu.</span>
            <button class="button button-secondary" type="submit">Simpan tempoh cookie</button>
        </form>
    </article>

    <article class="card">
        <h2>Task mingguan (kelayakan withdrawal)</h2>
        <form method="post" action="{{ route('admin.affiliate.settings.task') }}" class="adm-form">
            @csrf @method('PUT')
            <label>Minimum perkongsian seminggu<input name="weekly_share_target" type="number" min="0" max="1000" value="{{ old('weekly_share_target', $task['shares']) }}" required></label>
            <label>Minimum unique clicks seminggu<input name="weekly_unique_click_target" type="number" min="0" max="100000" value="{{ old('weekly_unique_click_target', $task['clicks']) }}" required></label>
            <span class="adm-help">Dikira untuk minggu semasa (Isnin 00:00 – Ahad 23:59). Perkongsian = download / salin caption / share poster dari Studio Poster. Unique clicks = pelawat berbeza yang klik link affiliate.</span>
            <button class="button button-secondary" type="submit">Simpan task mingguan</button>
        </form>
    </article>

    <article class="card adm-scroll">
        <h2>Sejarah versi kadar</h2>
        <table class="adm-table">
            <thead><tr><th>Versi</th><th>Berkuat kuasa</th><th>Tingkat</th></tr></thead>
            <tbody>@foreach ($history as $h)<tr><td>#{{ $h->id }}</td><td>{{ $h->effective_from->format('d/m/Y H:i') }}</td><td>@foreach ($h->tiers as $t){{ $t['up_to'] ? '≤RM'.number_format((float) $t['up_to']) : 'selebihnya' }}: {{ rtrim(rtrim($t['rate'], '0'), '.') }}%@if (! $loop->last) · @endif @endforeach</td></tr>@endforeach</tbody>
        </table>
    </article>
</div>

<script>
(function () {
    var body = document.querySelector('#tier-table tbody');
    function renumber() {
        Array.prototype.forEach.call(body.rows, function (row, i) {
            row.querySelector('.tier-no').textContent = i + 1;
            row.querySelectorAll('input').forEach(function (input) { input.name = input.name.replace(/tiers\[\d+\]/, 'tiers[' + i + ']'); });
        });
    }
    document.getElementById('tier-add').addEventListener('click', function () {
        var last = body.rows[body.rows.length - 1];
        var row = last.cloneNode(true);
        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        body.appendChild(row);
        renumber();
    });
    body.addEventListener('click', function (e) {
        if (!e.target.classList.contains('tier-remove')) return;
        if (body.rows.length <= 1) return;
        e.target.closest('tr').remove();
        renumber();
    });
})();
</script>
@endsection
