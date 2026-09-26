@extends('layouts.client', ['title' => 'Status Bayaran | NatNetwork Synergy'])

@php
    $initial = $payment ? [
        'status' => $payment->status,
        'invoice' => [
            'number' => $payment->invoice->number,
            'total' => number_format((float) $payment->invoice->total, 2),
            'amount_paid' => number_format((float) $payment->invoice->amount_paid, 2),
            'outstanding' => number_format($payment->invoice->outstandingCents() / 100, 2),
        ],
    ] : null;
@endphp

@section('content')
<section class="pay-card" x-data="paymentStatus(@js($initial))" x-init="init()">
    @if (! $payment)
        <div class="pay-icon pay-icon-warn">@include('builder.partials.icon', ['name' => 'info'])</div>
        <p class="eyebrow">Bayaran</p>
        <h1>Status bayaran tidak dapat disahkan</h1>
        <p class="lead">Pautan ini tidak sah atau sudah tamat tempoh. Jika anda telah membayar, sila semak portal client — status akan dikemas kini secara automatik selepas pengesahan daripada Billplz.</p>
        @auth('client')<a class="button" href="{{ route('client.dashboard') }}">Masuk ke Portal Client</a>@endauth
    @else
        <p class="eyebrow">Bayaran @if ($payment->is_sandbox)<span class="pay-sandbox">SANDBOX (ujian)</span>@endif</p>

        {{-- Ikon status: bertukar reaktif tanpa refresh halaman apabila callback Billplz disahkan. --}}
        <div class="pay-icon" :class="{ 'pay-icon-pending': isPending(), 'pay-icon-ok': isPaid(), 'pay-icon-warn': isReview(), 'pay-icon-fail': isFailed() }">
            <svg x-show="isPending()" class="pay-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-opacity=".2"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
            <svg x-show="isPaid()" x-cloak class="pay-pop" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <svg x-show="isFailed()" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            <svg x-show="isReview()" x-cloak viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5"/><path d="M12 7v6l4 2" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
        </div>

        <h1 x-show="isPending()">Bayaran sedang diproses dan disahkan.</h1>
        <h1 x-show="isPaid()" x-cloak>Bayaran berjaya!</h1>
        <h1 x-show="isReview()" x-cloak>Bayaran sedang disemak</h1>
        <h1 x-show="isFailed()" x-cloak>Bayaran tidak berjaya</h1>

        <p class="lead" x-show="isPending()">Kami sedang menunggu pengesahan rasmi daripada Billplz. Halaman ini akan <b>bertukar secara automatik</b> sebaik sahaja bayaran disahkan — tiada perlu muat semula.</p>
        <p class="lead" x-show="isPaid()" x-cloak>Bayaran RM <span x-text="state.invoice.amount_paid"></span> untuk invois <b x-text="state.invoice.number"></b> telah disahkan. Terima kasih!</p>
        <p class="lead" x-show="isReview()" x-cloak>Bayaran untuk invois <b x-text="state.invoice.number"></b> memerlukan semakan pasukan kami. Kami akan menghubungi anda tidak lama lagi.</p>
        <p class="lead" x-show="isFailed()" x-cloak>Bayaran untuk invois <b x-text="state.invoice.number"></b> tidak berjaya. Anda boleh cuba semula dengan selamat.</p>

        {{-- Invois sebenar — bukan sekadar amaun, supaya pelanggan nampak rekod penuh yang disahkan. --}}
        <div class="pay-invoice">
            <div class="pay-invoice-row"><span>No. Invois</span><b x-text="state.invoice.number"></b></div>
            <div class="pay-invoice-row"><span>Jumlah Invois</span><b>RM <span x-text="state.invoice.total"></span></b></div>
            <div class="pay-invoice-row"><span>Telah Dibayar</span><b x-text="'RM ' + state.invoice.amount_paid"></b></div>
            <div class="pay-invoice-row" x-show="Number(state.invoice.outstanding) > 0"><span>Baki Tertunggak</span><b x-text="'RM ' + state.invoice.outstanding"></b></div>
            <div class="pay-invoice-status">
                <span class="pay-badge" :class="{ 'is-pending': isPending(), 'is-ok': isPaid(), 'is-warn': isReview(), 'is-fail': isFailed() }" x-text="statusLabel()"></span>
                <span class="pay-live" x-show="polling" x-cloak><i class="pay-live-dot"></i> Menyemak secara automatik…</span>
            </div>
        </div>

        <div class="button-row">
            @auth('client')
                @if ($payment->invoice->customer_user_id === auth('client')->id())
                    <a class="button" href="{{ route('client.dashboard') }}" x-show="isPaid()" x-cloak>Masuk ke Portal Client @include('builder.partials.icon', ['name' => 'arrow-right'])</a>
                    <a class="button button-secondary" href="{{ route('client.billing.invoice', $payment->invoice_id) }}">Lihat invois &amp; resit</a>
                    @if ($payment->invoice->source_type === 'Quotation')
                        <a class="button button-secondary" href="{{ route('builder.start') }}" x-show="isFailed()" x-cloak>Kembali ke Start Project</a>
                    @endif
                @endif
            @endauth
            @auth('partner')
                @if ($payment->invoice->source_type === 'PartnerCapital')<a class="button" href="{{ route('partner.onboarding') }}">Kembali ke portal Partnership</a>@endif
            @endauth
            @auth('admin')
                <a class="button" href="{{ route('admin.billing.invoice', $payment->invoice_id) }}">Lihat invois di Admin</a>
            @endauth
        </div>
    @endif
</section>

@once
<script>
function paymentStatus(initial) {
    return {
        state: initial,
        polling: false,
        timer: null,
        init() {
            if (this.state && this.isPending()) this.poll();
        },
        isPending() { return ['PENDING', 'PROCESSING'].includes(this.state?.status); },
        isPaid() { return this.state?.status === 'PAID'; },
        isReview() { return this.state?.status === 'REVIEW_REQUIRED'; },
        isFailed() { return this.state?.status === 'FAILED'; },
        statusLabel() {
            return { PENDING: 'Menunggu bayaran', PROCESSING: 'Sedang diproses', PAID: 'Dibayar', REVIEW_REQUIRED: 'Dalam semakan', FAILED: 'Tidak berjaya' }[this.state?.status] || this.state?.status;
        },
        poll() {
            this.polling = true;
            this.timer = setTimeout(async () => {
                try {
                    const res = await fetch('{{ route('billing.billplz.status') }}' + window.location.search, { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    if (data.found) {
                        this.state = { status: data.status, invoice: data.invoice };
                    }
                } catch (e) { /* cuba lagi pusingan seterusnya */ }
                if (this.isPending()) this.poll();
                else this.polling = false;
            }, 3000);
        },
    };
}
</script>
@endonce
@endsection
