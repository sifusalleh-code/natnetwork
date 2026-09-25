@extends('layouts.client', ['title' => 'Status Bayaran | NatNetwork Synergy'])

@section('content')
<section class="auth-card">
    <p class="eyebrow">Bayaran @if ($payment?->is_sandbox) · SANDBOX (ujian) @endif</p>
    @if (! $payment)
        <h1>Status bayaran tidak dapat disahkan</h1>
        <p class="lead">Pautan ini tidak sah. Jika anda telah membayar, status akan dikemas kini secara automatik selepas pengesahan daripada Billplz.</p>
    @elseif ($payment->status === 'PAID')
        <h1>Bayaran berjaya</h1>
        <p class="lead">Bayaran RM {{ $payment->amount() }} untuk invois {{ $payment->invoice->number }} telah disahkan.</p>
    @elseif ($payment->status === 'REVIEW_REQUIRED')
        <h1>Bayaran sedang disemak</h1>
        <p class="lead">Bayaran untuk invois {{ $payment->invoice->number }} memerlukan semakan pasukan kami. Kami akan menghubungi anda.</p>
    @elseif ($payment->status === 'FAILED')
        <h1>Bayaran tidak berjaya</h1>
        <p class="lead">Bayaran untuk invois {{ $payment->invoice->number }} tidak berjaya. Anda boleh cuba semula.</p>
    @else
        <h1>Bayaran sedang disahkan</h1>
        <p class="lead">Kami sedang menunggu pengesahan daripada Billplz untuk invois {{ $payment->invoice->number }}. Muat semula halaman ini sebentar lagi.</p>
    @endif
    @auth('client')
        @if ($payment && $payment->invoice->customer_user_id === auth('client')->id())
            @if ($payment->status === 'PAID')
                <a class="button" href="{{ route('client.dashboard') }}">Masuk ke Portal Client</a>
                <a class="button button-secondary" href="{{ route('client.billing.invoice', $payment->invoice_id) }}">Lihat invois &amp; resit</a>
            @elseif ($payment->invoice->source_type === 'Quotation')
                <a class="button" href="{{ route('client.billing.invoice', $payment->invoice_id) }}">Cuba bayar semula (bil sama)</a>
                <a class="button button-secondary" href="{{ route('builder.start') }}">Kembali ke Start Project</a>
            @else
                <a class="button" href="{{ route('client.billing.invoice', $payment->invoice_id) }}">Kembali ke portal</a>
            @endif
        @endif
    @endauth
    @auth('partner')
        @if ($payment && $payment->invoice->source_type === 'PartnerCapital')<a class="button" href="{{ route('partner.onboarding') }}">Kembali ke portal Partnership</a>@endif
    @endauth
    @auth('admin')
        @if ($payment)<a class="button" href="{{ route('admin.billing.invoice', $payment->invoice_id) }}">Lihat invois di Admin</a>@endif
    @endauth
</section>
@endsection
