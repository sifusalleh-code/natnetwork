<script>
(function () {
    // Tanda kategori aktif ketika skrol + fade-up ringan. Tanpa JS, kandungan kekal dipaparkan.
    var links = document.querySelectorAll('[data-svc-link]');
    var sections = document.querySelectorAll('[data-svc-section]');
    if (! ('IntersectionObserver' in window)) return;
    function setActive(key) {
        links.forEach(function (a) {
            var on = a.getAttribute('data-svc-link') === key;
            a.classList.toggle('is-active', on);
            if (on) { a.setAttribute('aria-current', 'true'); a.scrollIntoView({ block: 'nearest', inline: 'nearest' }); } else { a.removeAttribute('aria-current'); }
        });
    }
    var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) setActive(e.target.getAttribute('data-svc-section')); });
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach(function (s) { spy.observe(s); });
    var hero = document.querySelector('.svc-hero');
    if (hero) new IntersectionObserver(function (e) { if (e[0].isIntersecting) setActive('all'); }, { rootMargin: '-45% 0px -50% 0px' }).observe(hero);

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var page = document.querySelector('.svc-page');
    page.classList.add('svc-js');
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
    }, { threshold: 0.1 });
    page.querySelectorAll('.svc-reveal, .pkg-card, .addon-card').forEach(function (el) { io.observe(el); });
})();
</script>
