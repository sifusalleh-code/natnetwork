<ol class="nn-steps" aria-label="Langkah pendaftaran Partnership">
    @foreach (['Daftar', 'Terma & syarat', 'Maklumat & bayaran', 'Lengkapkan profil', 'Dashboard'] as $i => $label)
        <li @class(['is-done' => $i + 1 < $current, 'is-current' => $i + 1 === $current]) @if ($i + 1 === $current) aria-current="step" @endif>{{ $label }}</li>
    @endforeach
</ol>
