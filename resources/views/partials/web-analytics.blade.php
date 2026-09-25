@isset($nnPageView)
@php($nnCtaPaths = collect(['builder.start' => 'mulakan-projek', 'services' => 'lihat-servis', 'contact' => 'hubungi', 'affiliate.register' => 'daftar-affiliate', 'register' => 'daftar-client', 'gallery.examples' => 'demo', 'opportunity' => 'peluang'])->mapWithKeys(fn ($key, $route) => [rtrim((string) parse_url(route($route), PHP_URL_PATH), '/') ?: '/' => $key]))
<script>
(function () {
    var pv = @json($nnPageView), token = @json(csrf_token()), paths = @json($nnCtaPaths);
    function send(url, extra) {
        var d = new FormData(); d.append('_token', token); d.append('pv', pv);
        for (var k in extra) d.append(k, extra[k]);
        try { if (navigator.sendBeacon && navigator.sendBeacon(url, d)) return; } catch (e) {}
        try { fetch(url, { method: 'POST', body: d, credentials: 'same-origin', keepalive: true }); } catch (e) {}
    }
    function ping() { if (document.visibilityState === 'visible') send(@json(route('analytics.ping')), {}); }
    setTimeout(ping, 10000);
    setInterval(ping, 30000);
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') send(@json(route('analytics.ping')), {}); });
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]'); if (! a) return;
        var href = a.getAttribute('href') || '', key = null;
        if (/^mailto:/i.test(href)) key = 'email';
        else if (/^tel:/i.test(href)) key = 'telefon';
        else if (/wa\.me|whatsapp\.com/i.test(href)) key = 'whatsapp';
        else if (/button|btn|cta|submit/i.test(a.className)) {
            try { var p = new URL(a.href, location.href); if (p.host === location.host) key = paths[p.pathname.replace(/\/$/, '') || '/'] || null; } catch (err) {}
        }
        if (key) send(@json(route('analytics.event')), { cta: key });
    }, true);
})();
</script>
@endisset
