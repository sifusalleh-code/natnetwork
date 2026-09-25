@extends('layouts.admin', ['title' => 'Poster Affiliate'])

@section('content')
<header class="adm-top"><div><h1>Poster Affiliate</h1><p>Bahan promosi untuk Studio Poster. Caption boleh guna <code>{link}</code> untuk kedudukan referral link; jika tiada, link ditambah di hujung caption.</p></div></header>

<article class="card">
    <h2>Tambah poster</h2>
    <form method="post" action="{{ route('admin.affiliate.posters.store') }}" enctype="multipart/form-data" class="adm-form">
        @csrf
        <label>Tajuk<input name="title" maxlength="120" value="{{ old('title') }}" required></label>
        <label>Caption<textarea name="caption" maxlength="2000" rows="5" required placeholder="Contoh: Nak website profesional untuk bisnes anda? Mulakan di sini: {link}">{{ old('caption') }}</textarea></label>
        <label>Susunan<input name="display_order" type="number" min="0" max="999" value="{{ old('display_order', 0) }}"></label>
        <label>Imej poster<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>
        <span class="adm-help">JPG, PNG atau WebP, maksimum 5MB. Disyorkan 1080×1350 (Instagram) atau 1080×1080.</span>
        <button class="button" type="submit">Tambah poster</button>
    </form>
</article>

<div class="afx-admin-posters">
    @forelse ($posters as $poster)
        <article class="card">
            <div class="afx-admin-poster">
                <img src="{{ route('admin.affiliate.posters.image', $poster) }}" alt="Poster {{ $poster->title }}" loading="lazy">
                <form method="post" action="{{ route('admin.affiliate.posters.update', $poster) }}" enctype="multipart/form-data" class="adm-form">
                    @csrf @method('PUT')
                    <p><span @class(['adm-badge', 'PAID' => $poster->is_active])>{{ $poster->is_active ? 'AKTIF' : 'TIDAK AKTIF' }}</span> · {{ $shareCounts[$poster->id] ?? 0 }} perkongsian</p>
                    <label>Tajuk<input name="title" maxlength="120" value="{{ $poster->title }}" required></label>
                    <label>Caption<textarea name="caption" maxlength="2000" rows="4" required>{{ $poster->caption }}</textarea></label>
                    <label>Susunan<input name="display_order" type="number" min="0" max="999" value="{{ $poster->display_order }}"></label>
                    <label>Tukar imej (pilihan)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                    <label class="check"><input type="checkbox" name="is_active" value="1" @checked($poster->is_active)> Papar kepada affiliate</label>
                    <button class="button button-secondary" type="submit">Simpan</button>
                </form>
            </div>
        </article>
    @empty
        <article class="card"><p>Belum ada poster.</p></article>
    @endforelse
</div>
@endsection
