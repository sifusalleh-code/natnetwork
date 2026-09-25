<article class="card afx-link" x-data="{ copied: false }">
    <div><h2>Referral link anda</h2><p>Kongsi link ini. Pelawat yang mendaftar sebagai client dalam tempoh cookie akan dikaitkan dengan anda.</p></div>
    <div class="afx-link-row">
        <input type="text" readonly value="{{ $link }}" aria-label="Referral link" x-ref="link" @focus="$event.target.select()">
        <button type="button" class="z-button" @click="navigator.clipboard.writeText($refs.link.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"><span x-text="copied ? 'Disalin ✓' : 'Salin link'">Salin link</span></button>
    </div>
</article>
