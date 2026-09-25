@extends('layouts.portal', ['title' => 'Projects', 'active' => 'projects'])

@section('content')
<header class="prt-top"><div><h1>Projects</h1><p>Kemajuan projek, bahan yang diperlukan dan deliverable.</p></div></header>

@forelse ($projects as $p)
    <article class="card">
        <div class="prt-action">
            <div>
                <b>{{ $p->name }}</b>
                <span>{{ $p->number }} · <span @class(['prt-badge', 'ok' => $p->status === 'COMPLETED', 'warn' => in_array($p->status, ['WAITING_FOR_CLIENT', 'WAITING_FOR_PAYMENT', 'CLIENT_REVIEW', 'FINAL_APPROVAL'], true), 'bad' => $p->status === 'CANCELLED'])>{{ $p->clientLabel() }}</span></span>
            </div>
            <a class="button" href="{{ route('client.projects.show', $p) }}">Buka</a>
        </div>
        @include('client.portal.partials.progress', ['value' => $p->progress])
    </article>
@empty
    <section class="card"><h2>Belum ada projek aktif.</h2><p class="prt-empty">Projek akan muncul di sini selepas deposit disahkan dan pesanan anda dikonfirmasi.</p></section>
@endforelse
@endsection
