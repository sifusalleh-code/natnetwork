<div style="margin-top: .6rem;" aria-label="Progress {{ $value }}%">
    <div style="height: .6rem; border-radius: 99rem; background: var(--soft-strong); overflow: hidden;"><div style="width: {{ max(0, min(100, (int) $value)) }}%; height: 100%; background: var(--gold-strong);"></div></div>
    <span class="prt-empty" style="font-size: .85rem;">{{ (int) $value }}% siap</span>
</div>
