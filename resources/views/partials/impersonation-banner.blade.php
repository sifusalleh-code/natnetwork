@if ($imp = session('impersonation'))
    <div style="position: sticky; top: 0; z-index: 50; display: flex; flex-wrap: wrap; gap: .6rem; justify-content: space-between; align-items: center; padding: .55rem 1rem; background: #a72c42; color: #fff; font-weight: 800; font-size: .88rem;">
        <span>MOD ADMIN — anda log masuk sebagai {{ $imp['name'] }} ({{ $imp['type'] }}). Semua tindakan direkod. Bayaran & kelulusan disekat.</span>
        <form method="post" action="{{ route('admin.impersonate.stop') }}" style="margin: 0;">@csrf<button type="submit" style="padding: .3rem .8rem; border: 0; border-radius: .4rem; background: #fff; color: #a72c42; font-weight: 800; cursor: pointer;">Keluar mod admin</button></form>
    </div>
@endif
