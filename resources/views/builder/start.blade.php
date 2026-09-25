@extends('layouts.public', ['title' => 'Mula Projek | NatNetwork Synergy'])

@php
    $cfg = config('builder');
    $photo = fn (?string $id, int $w, int $h = 0) => $id ? (str_starts_with($id, 'photo-') ? $cfg['photo_cdn'].$id.'?auto=format&fit=crop&q=70&w='.$w.($h ? '&h='.$h : '') : asset($id)) : null;
    $questionsByCode = $questions->keyBy('code');
    $mapped = collect($cfg['steps'])->pluck('codes')->flatten()->all();
    $steps = collect($cfg['steps'])->map(function ($step, $i) use ($questionsByCode, $mapped, $cfg) {
        $codes = $step['codes'];
        if ($i === count($cfg['steps']) - 1) {
            $codes = array_merge($codes, $questionsByCode->keys()->diff($mapped)->values()->all());
        }
        $step['questions'] = collect($codes)->map(fn ($c) => $questionsByCode->get($c))->filter()->values();

        return $step;
    });
    $isDirect = $builderSession->entry_path === 'DIRECT_SELECTION';
    $lastIndex = $steps->count() - 1;
    $wizard = [
        'steps' => $steps->map(fn ($s) => ['key' => $s['key'], 'label' => $s['label'], 'codes' => $s['questions']->pluck('code')->values()])->values(),
        'questions' => $questions->mapWithKeys(fn ($q) => [$q->code => ['type' => $q->question_type, 'required' => (bool) $q->is_required, 'condition' => $q->condition, 'label' => $q->label, 'options' => $q->options->pluck('label', 'code')]]),
        'answers' => old('answers', $answers),
        'step' => min(max((int) $builderStep, 0), $steps->count()),
        'isDirect' => $isDirect,
        'packageId' => old('service_package_id', $builderSession->service_package_id),
        'packages' => $services->flatMap(fn ($s) => $s->packages)->mapWithKeys(fn ($p) => [$p->id => $p->name.' · '.$p->price_label]),
        'packageData' => $services->flatMap(fn ($s) => $s->packages->map(fn ($p) => ['id' => (string) $p->id, 'slug' => $p->slug, 'name' => $p->name, 'label' => $p->price_label, 'cents' => $p->price_amount !== null ? (int) round((float) $p->price_amount * 100) : null, 'service' => $s->slug, 'group' => $s->name,
            'addons' => $p->packageAddons->filter(fn ($pa) => $pa->addon && $pa->addon->is_active)->map(fn ($pa) => ['id' => (string) $pa->addon_id, 'slug' => $pa->addon->slug, 'name' => $pa->displayName(), 'label' => $pa->price_label, 'cents' => $pa->price_amount !== null ? (int) round((float) $pa->price_amount * 100) : null, 'monthly' => $pa->price_type === 'monthly'])->values()]))->values(),
        'addonCatalog' => $addons->map(fn ($a) => ['id' => (string) $a->id, 'slug' => $a->slug, 'name' => $a->name, 'label' => $a->price_label, 'cents' => $a->price_amount !== null ? (int) round((float) $a->price_amount * 100) : null, 'monthly' => $a->price_type === 'monthly'])->values(),
        'addonIds' => array_map('strval', old('addon_ids', $builderSession->addon_ids ?? [])),
        'serviceMap' => $cfg['project_type_services'] ?? [],
        'functionAddons' => $cfg['function_addons'] ?? [],
        'packageIncludes' => $cfg['package_includes'] ?? [],
        'files' => $builderFiles,
        'colours' => collect($cfg['colours'])->map(fn ($c) => ['desc' => $c['desc'], 'swatch' => $c['swatch']]),
        'preview' => collect($cfg['photos']['preview'])->map(fn ($id) => $photo($id, 900, 640)),
        'urls' => ['save' => route('builder.save'), 'files' => route('builder.files.store')],
        'csrf' => csrf_token(),
    ];
@endphp

@section('content')
<div class="bw" x-data="builderWizard(@js($wizard))" :style="accentVars()">
    {{-- HERO --}}
    <section class="bw-hero" aria-labelledby="bw-title">
        <div class="bw-hero-photo" aria-hidden="true" style="background-image:url('{{ $photo($cfg['photos']['hero'], 1400, 700) }}')"></div>
        <div class="bw-wrap bw-hero-inner">
            <p class="bw-eyebrow">Mula projek</p>
            <h1 id="bw-title">Ceritakan apa yang <span>anda mahu capai.</span></h1>
            <p class="bw-lead">Pilih pakej sendiri atau jawab soalan ringkas dalam bahasa mudah. Kami susun jawapan anda kepada skop projek yang jelas, bukan memaksa anda memahami perkara teknikal.</p>
        </div>
    </section>

    @if (session('builder_status'))<div class="bw-wrap"><p class="bw-flash" role="status">{{ session('builder_status') }}</p></div>@endif

    @if (! $builderSession->entry_path)
        {{-- PILIH CARA MULA --}}
        <section class="bw-wrap bw-entry" aria-label="Pilih cara mula">
            @if ($selectedPackage)
                <article class="bw-card bw-selected">
                    <div><p class="bw-kicker">Pakej pilihan anda</p><h2>{{ $selectedPackage->name }}</h2><p class="bw-price">{{ $selectedPackage->price_label }}</p>@if ($selectedPackage->summary)<p class="bw-muted">{{ $selectedPackage->summary }}</p>@endif</div>
                    <form method="post" action="{{ route('builder.entry') }}">@csrf<input type="hidden" name="entry_path" value="DIRECT_SELECTION"><input type="hidden" name="package" value="{{ $selectedPackage->slug }}"><button class="bw-btn bw-btn-primary" type="submit">Teruskan dengan pakej ini @include('builder.partials.icon', ['name' => 'arrow-right'])</button></form>
                </article>
            @endif
            <div class="bw-entry-grid">
                <form method="post" action="{{ route('builder.entry') }}" class="bw-card bw-entry-card">@csrf<input type="hidden" name="entry_path" value="DIRECT_SELECTION">
                    <div class="bw-entry-photo" style="background-image:url('{{ $photo($cfg['photos']['entry_direct'], 900, 460) }}')" aria-hidden="true"></div>
                    <div class="bw-entry-body"><p class="bw-kicker">Saya Dah Tahu</p><h2>Saya mahu pilih servis atau pakej.</h2><p class="bw-muted">Pilih titik mula yang paling hampir dengan keperluan anda.</p><button class="bw-btn bw-btn-primary" type="submit">Pilih pakej @include('builder.partials.icon', ['name' => 'arrow-right'])</button></div>
                </form>
                <form method="post" action="{{ route('builder.entry') }}" class="bw-card bw-entry-card">@csrf<input type="hidden" name="entry_path" value="GUIDED">
                    <div class="bw-entry-photo" style="background-image:url('{{ $photo($cfg['photos']['entry_guided'], 900, 460) }}')" aria-hidden="true"></div>
                    <div class="bw-entry-body"><p class="bw-kicker">Bantu Saya Pilih</p><h2>Saya mahu panduan.</h2><p class="bw-muted">Jawab soalan mudah. Soalan yang tidak berkaitan akan dilangkau.</p><button class="bw-btn bw-btn-primary" type="submit">Mula soalan @include('builder.partials.icon', ['name' => 'arrow-right'])</button></div>
                </form>
            </div>
        </section>
    @elseif (! $identityVerified)
        {{-- MAKLUMAT ANDA: wajib sebelum soal jawab bermula --}}
        <section class="bw-wrap bw-identity" aria-labelledby="bw-id-title">
            <div class="bw-card bw-verify">
                <header class="bw-step-head">
                    <span class="bw-step-icon">@include('builder.partials.icon', ['name' => 'mail'])</span>
                    <div><p class="bw-step-count">Langkah pertama</p><h2 id="bw-id-title">Maklumat anda</h2><p class="bw-step-sub">Isi nama, no. telefon dan emel sebelum soal jawab bermula. Kemajuan Start Project disimpan automatik dan boleh disambung dalam tempoh {{ \App\Engines\Sales\Services\BuilderSessionService::RESUME_DAYS }} hari.</p></div>
                </header>
                <form method="post" action="{{ route('builder.identity') }}" class="bw-form-grid">@csrf
                    <label class="bw-label">Nama<input class="bw-input" id="name" name="name" value="{{ old('name', $builderSession->contact_name) }}" autocomplete="name" required>@error('name')<span class="bw-error">{{ $message }}</span>@enderror</label>
                    <label class="bw-label">Syarikat (pilihan)<input class="bw-input" id="company" name="company" value="{{ old('company', $builderSession->contact_company) }}" autocomplete="organization"></label>
                    <label class="bw-label">Emel<input class="bw-input" id="email" name="email" type="email" value="{{ old('email', $builderSession->contact_email) }}" autocomplete="email" required>@error('email')<span class="bw-error">{{ $message }}</span>@enderror</label>
                    <label class="bw-label">No. telefon<input class="bw-input" id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone', $builderSession->contact_phone) }}" autocomplete="tel" required>@error('phone')<span class="bw-error">{{ $message }}</span>@enderror</label>
                    <div class="bw-form-actions"><button class="bw-btn bw-btn-primary" type="submit">Teruskan @include('builder.partials.icon', ['name' => 'arrow-right'])</button></div>
                </form>
                @if (session('builder_verification_pending') || $errors->has('code'))
                    <p class="bw-muted bw-otp-note">Kod enam digit telah dihantar ke <b>{{ $builderSession->contact_email }}</b>. Emel yang sudah disahkan tidak perlu disahkan semula pada peranti ini.</p>
                    <form method="post" action="{{ route('builder.verify') }}" class="bw-form-grid bw-otp">@csrf
                        <label class="bw-label">Kod enam digit<input class="bw-input" id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus>@error('code')<span class="bw-error">{{ $message }}</span>@enderror</label>
                        <div class="bw-form-actions"><button class="bw-btn bw-btn-primary" type="submit">Sahkan emel &amp; mula soal jawab</button></div>
                    </form>
                @endif
            </div>
        </section>
    @else
        {{-- ALIRAN (satu-satunya penunjuk langkah) --}}
        <nav class="bw-wrap" aria-label="Langkah mula projek">
            <ol class="bw-flow" x-ref="flow">
                @foreach ($steps as $i => $step)
                    <li :class="flowClass({{ $i }})" @if ($i > 0)data-arrow @endif>
                        <button type="button" class="bw-flow-btn" @click="goTo({{ $i }})" :disabled="! canVisit({{ $i }})" :aria-current="step === {{ $i }} ? 'step' : null">
                            <span class="bw-flow-dot"><span x-show="! done({{ $i }})">{{ $i + 1 }}</span><span x-show="done({{ $i }})" x-cloak>@include('builder.partials.icon', ['name' => 'check'])</span></span>
                            <span class="bw-flow-label">{{ $step['label'] }}</span>
                            <span class="bw-sr" x-text="done({{ $i }}) ? '(selesai)' : (step === {{ $i }} ? '(semasa)' : '')"></span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </nav>

        @include('builder.partials.journey', ['journey' => $journey])

        <form method="post" action="{{ route('builder.save') }}" class="bw-wrap" @submit.prevent="next()" novalidate x-ref="form">
            @csrf
            <div class="bw-card bw-main" x-show="step <= {{ $lastIndex }}">
                {{-- ANGGARAN KOS: pakej + add-on (harga daripada katalog) --}}
                <div class="bw-cost is-top" x-show="selectedPackage()" x-cloak aria-live="polite">
                    <div class="bw-cost-head">
                        <p class="bw-kicker">Anggaran kos projek</p>
                        <strong x-text="rm(costTotal())"></strong>
                    </div>
                    <ul class="bw-cost-lines">
                        <li><span><b x-text="selectedPackage()?.name"></b> <small x-text="selectedPackage()?.label"></small></span><button type="button" class="bw-link" @click="changePackage()">Tukar pakej</button></li>
                        <template x-for="a in selectedAddons()" :key="a.id"><li><span>Add-on: <b x-text="a.name"></b> <small x-text="a.label"></small></span><button type="button" class="bw-link is-remove" @click="removeAddon(a.id)" :aria-label="'Buang add-on ' + a.name">Buang</button></li></template>
                    </ul>
                    <p class="bw-muted bw-cost-note">Harga daripada katalog semasa. Harga bertanda "+" ialah harga permulaan; jumlah dimuktamadkan dalam quotation selepas Master Specification diluluskan.</p>
                </div>

                <div class="bw-question">
                    @foreach ($steps as $i => $step)
                        <section class="bw-step" x-show="step === {{ $i }}" @if ($i !== min($wizard['step'], $lastIndex)) x-cloak @endif aria-labelledby="bw-step-{{ $i }}">
                            <header class="bw-step-head">
                                <span class="bw-step-icon">@include('builder.partials.icon', ['name' => $step['icon']])</span>
                                <div>
                                    <p class="bw-step-count">Langkah {{ $i + 1 }} daripada {{ $steps->count() }}</p>
                                    <h2 id="bw-step-{{ $i }}" tabindex="-1" x-ref="h{{ $i }}">{{ $step['label'] }}</h2>
                                    <p class="bw-step-sub">{{ $step['subtitle'] }}</p>
                                </div>
                            </header>
                            @if ($step['info'])<p class="bw-info">@include('builder.partials.icon', ['name' => 'info'])<span>{{ $step['info'] }}</span></p>@endif


                            @foreach ($step['questions'] as $qi => $question)
                                @php($display = $cfg['option_display'][$question->code] ?? 'chip')
                                @php($isPrimary = $qi === 0)
                                <fieldset class="bw-field" x-show="visible(@js($question->code))" @if ($question->condition) x-cloak @endif>
                                    @if ($isPrimary && $step['section'])
                                        <legend class="bw-section">{{ $step['section'] }} @if ($question->is_required && ! ($isDirect && $question->code === 'project_type'))<span class="bw-req">*</span>@endif</legend>
                                    @elseif (! $isPrimary || ! in_array($question->question_type, ['textarea'], true))
                                        <legend class="bw-label">{{ $question->label }} @if ($question->is_required)<span class="bw-req">*</span>@endif</legend>
                                    @else
                                        <legend class="bw-sr">{{ $question->label }}</legend>
                                    @endif

                                    @if (in_array($question->question_type, ['single_choice', 'multiple_choice'], true))
                                        @php($multi = $question->question_type === 'multiple_choice')
                                        @php($inputType = $multi ? 'checkbox' : 'radio')
                                        @php($inputName = $multi ? 'answers['.$question->code.'][]' : 'answers['.$question->code.']')
                                        <div @class(['bw-opts', 'is-'.$display, 'is-'.$display.'-'.min($question->options->count(), 8)])>
                                            @foreach ($question->options as $option)
                                                <label class="bw-opt" :class="{ 'is-on': isOn(@js($question->code), @js($option->code)) }">
                                                    <input type="{{ $inputType }}" name="{{ $inputName }}" value="{{ $option->code }}" x-model="answers[@js($question->code)]">
                                                    @if ($display === 'colour')
                                                        @php($c = $cfg['colours'][$option->code] ?? null)
                                                        <span class="bw-opt-photo" style="background-image:url('{{ $photo($c['photo'] ?? null, 420, 120) }}')" aria-hidden="true"></span>
                                                        <span class="bw-opt-row"><span class="bw-radio" aria-hidden="true"></span><span class="bw-swatch" aria-hidden="true">@foreach ($c['swatch'] ?? [] as $hex)<i style="background:{{ $hex }}"></i>@endforeach</span></span>
                                                        <span class="bw-opt-name">{{ $option->label }}</span>
                                                        @if ($c)<span class="bw-opt-desc">{{ $c['desc'] }}</span>@endif
                                                    @elseif ($display === 'photo')
                                                        <span class="bw-opt-photo is-tall" style="background-image:url('{{ $photo($cfg['styles'][$option->code] ?? null, 420, 260) }}')" aria-hidden="true"></span>
                                                        <span class="bw-opt-row"><span class="bw-radio" aria-hidden="true"></span><span class="bw-opt-name">{{ $option->label }}</span></span>
                                                    @elseif ($display === 'icon')
                                                        <span class="bw-opt-icon">@include('builder.partials.icon', ['name' => $cfg['option_icons'][$question->code][$option->code] ?? 'grid'])</span>
                                                        <span class="bw-opt-name">{{ $option->label }}</span>
                                                        <span class="bw-opt-tick" aria-hidden="true">@include('builder.partials.icon', ['name' => 'check'])</span>
                                                    @else
                                                        <span class="bw-chip-box" aria-hidden="true">@include('builder.partials.icon', ['name' => 'check'])</span>
                                                        <span class="bw-opt-name">{{ $option->label }}</span>
                                                        @if ($question->code === 'website_functions')<span class="bw-fn-badge" :class="fnBadgeClass(@js($option->code))" x-text="fnBadge(@js($option->code))" x-show="fnBadge(@js($option->code))"></span>@endif
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif ($question->code === 'colour_custom')
                                        <div class="bw-colour-custom">
                                            <input type="color" aria-label="Pilih warna tersuai" :value="validHex(answers.colour_custom) ? answers.colour_custom : '#0b5fd6'" @input="answers.colour_custom = $event.target.value">
                                            <input class="bw-input" name="answers[colour_custom]" maxlength="255" placeholder="Contoh: #0B5FD6 atau hijau zamrud" x-model="answers.colour_custom">
                                        </div>
                                    @elseif ($question->question_type === 'url')
                                        <input class="bw-input" type="url" inputmode="url" name="answers[{{ $question->code }}]" placeholder="https://" x-model="answers[@js($question->code)]">
                                    @elseif ($question->question_type === 'text')
                                        <input class="bw-input" name="answers[{{ $question->code }}]" maxlength="255" @if ($question->code === 'domain_name') placeholder="contoh: namabisnes.com.my" autocomplete="url" @endif x-model="answers[@js($question->code)]">
                                    @else
                                        <textarea class="bw-input bw-textarea" name="answers[{{ $question->code }}]" rows="{{ $isPrimary ? 7 : 4 }}" maxlength="5000" placeholder="{{ $isPrimary ? ($step['placeholder'] ?? '') : '' }}" x-model="answers[@js($question->code)]"></textarea>
                                    @endif
                                    <p class="bw-error" x-show="errors[@js('answers.'.$question->code)]" x-text="errors[@js('answers.'.$question->code)]" x-cloak></p>
                                    @error('answers.'.$question->code)<p class="bw-error">{{ $message }}</p>@enderror
                                </fieldset>
                                @if ($question->code === 'project_type')
                                    {{-- Pakej: selepas jenis projek, ditapis mengikut jenis projek. Harga dipapar terus. --}}
                                    <div class="bw-field" x-show="isDirect || answers.project_type" @unless ($isDirect || ($answers['project_type'] ?? null)) x-cloak @endunless>
                                        <label class="bw-label" for="service_package_id">Pilih pakej <span class="bw-req">*</span></label>
                                        <p class="bw-muted">Pakej disesuaikan dengan jenis projek. Fungsi yang sudah termasuk dalam pakej tidak dicaj.</p>
                                        <select id="service_package_id" name="service_package_id" class="bw-input" x-model="packageId">
                                            <option value="">Pilih pakej</option>
                                            <template x-for="p in availablePackages()" :key="p.id"><option :value="p.id" x-text="p.name + ' · ' + p.label" :selected="p.id === packageId"></option></template>
                                        </select>
                                        <p class="bw-error" x-show="errors.service_package_id" x-text="errors.service_package_id" x-cloak></p>
                                        @error('service_package_id')<p class="bw-error">{{ $message }}</p>@enderror
                                    </div>
                                @endif

                                @if (($question->code === 'content_logo' || $question->code === 'reference_available'))
                                    @php($kind = $question->code === 'content_logo' ? 'logo' : 'reference')
                                    <div class="bw-field bw-upload" x-show="{{ $kind === 'logo' ? "['available','upgrade-logo'].includes(answers.content_logo)" : "answers.reference_available === 'yes'" }}" x-cloak>
                                        <p class="bw-label">{{ $kind === 'logo' ? 'Muat naik logo' : 'Muat naik screenshot / imej rujukan' }} <span class="bw-muted">(pilihan)</span></p>
                                        <label class="bw-drop" :class="{ 'is-busy': uploading === '{{ $kind }}' }" @dragover.prevent @drop.prevent="upload('{{ $kind }}', $event.dataTransfer.files)">
                                            <input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" multiple @change="upload('{{ $kind }}', $event.target.files); $event.target.value = ''">
                                            <span class="bw-drop-icon">@include('builder.partials.icon', ['name' => 'upload'])</span>
                                            <span><b x-text="uploading === '{{ $kind }}' ? 'Memuat naik…' : 'Pilih fail atau seret ke sini'"></b><small>JPG, PNG, WebP atau PDF · maks 5MB · sehingga 5 fail</small></span>
                                        </label>
                                        <p class="bw-error" x-show="uploadError['{{ $kind }}']" x-text="uploadError['{{ $kind }}']" x-cloak></p>
                                        <ul class="bw-files">
                                            <template x-for="f in filesOf('{{ $kind }}')" :key="f.id">
                                                <li>
                                                    <template x-if="f.is_image"><img :src="f.url" alt="" width="44" height="44" loading="lazy"></template>
                                                    <template x-if="! f.is_image"><span class="bw-file-icon">@include('builder.partials.icon', ['name' => 'file'])</span></template>
                                                    <span class="bw-file-name" x-text="f.name"></span>
                                                    <button type="button" class="bw-file-del" @click="removeFile(f)" :aria-label="'Buang ' + f.name">@include('builder.partials.icon', ['name' => 'trash'])</button>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                @endif
                            @endforeach

                            @if ($step['key'] === 'addon')
                                <div class="bw-addons" role="group" aria-label="Add-on">
                                    <template x-for="a in pkgAddons()" :key="a.id">
                                        <label class="bw-addon" :class="{ 'is-on': isAddonOn(a) && ! addonIncluded(a), 'is-included': addonIncluded(a) }">
                                            <input type="checkbox" :checked="isAddonOn(a) || addonIncluded(a)" :disabled="addonIncluded(a)" @change="toggleAddon(a)">
                                            <span class="bw-addon-body">
                                                <b x-text="a.name"></b>
                                                <em class="bw-addon-note" x-show="addonIncluded(a)">Termasuk dalam pakej — tiada caj</em>
                                                <em class="bw-addon-note" x-show="! addonIncluded(a) && addonFunction(a) && isAddonOn(a)">Dipilih melalui soalan Fungsi</em>
                                                <em class="bw-addon-note" x-show="a.monthly">Caj bulanan — tidak dicampur dalam jumlah kos projek</em>
                                            </span>
                                            <span class="bw-addon-price" x-text="addonIncluded(a) ? 'Termasuk' : '+ ' + a.label"></span>
                                        </label>
                                    </template>
                                    <p class="bw-muted" x-show="! pkgAddons().length">Tiada add-on untuk pakej ini.</p>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>

                {{-- CONTOH PAPARAN --}}
                <aside class="bw-side" aria-label="Contoh paparan">
                    <h3>Contoh paparan</h3>
                    <div class="bw-mock" :data-style="answers.design_style || 'modern'" aria-hidden="true">
                        <div class="bw-mock-bar"><i></i><i></i><i></i><span x-text="mockDomain()"></span></div>
                        <div class="bw-mock-nav">
                            <template x-if="logoFile()"><img class="bw-mock-logo-img" :src="logoFile().url" alt=""></template>
                            <b class="bw-mock-logo" x-show="! logoFile()">LOGO</b>
                            <span class="bw-mock-links"><template x-for="l in mock().links"><span x-text="l"></span></template></span>
                            <span class="bw-mock-btn" x-text="mock().button"></span>
                        </div>
                        <div class="bw-mock-hero" :style="`background-image:url('${previewPhoto()}')`">
                            <div class="bw-mock-copy">
                                <b x-text="mock().title"></b>
                                <span>Kami bantu realisasikan idea anda dengan laman web yang profesional, pantas dan mesra pengguna.</span>
                                <em x-text="mock().cta"></em>
                            </div>
                        </div>
                    </div>

                    <div class="bw-ctx">
                        <template x-if="current().key === 'warna'">
                            <div class="bw-ctx-colour">
                                <p class="bw-ctx-title">Warna dipilih</p>
                                <div class="bw-ctx-row">
                                    <span class="bw-dots"><template x-for="c in palette()"><i :style="`background:${c}`"></i></template></span>
                                    <span><b x-text="optionLabel('colour_preference', answers.colour_preference) || 'Belum dipilih'"></b><small x-text="colourDesc()"></small></span>
                                </div>
                            </div>
                        </template>
                        <template x-if="current().key !== 'warna'">
                            <div>
                                <p class="bw-ctx-title">Pilihan anda</p>
                                <p class="bw-ctx-value" x-text="summary(step) || 'Belum ada pilihan untuk langkah ini.'"></p>
                                <p class="bw-ctx-cost" x-show="selectedPackage()" x-cloak><span x-text="selectedPackage()?.name + (selectedAddons().length ? ' + ' + selectedAddons().length + ' add-on' : '')"></span><b x-text="rm(costTotal())"></b></p>
                            </div>
                        </template>
                    </div>
                </aside>

                {{-- NAVIGASI --}}
                <div class="bw-nav">
                    <button type="button" class="bw-btn bw-btn-ghost" @click="back()" :disabled="step === 0 || busy">@include('builder.partials.icon', ['name' => 'arrow-left']) Kembali</button>
                    <button type="button" class="bw-btn bw-btn-ghost" @click="save(false)" :disabled="busy">@include('builder.partials.icon', ['name' => 'save']) Simpan</button>
                    <span class="bw-saved" role="status" aria-live="polite" x-text="notice"></span>
                    <button type="submit" class="bw-btn bw-btn-primary" :disabled="busy">
                        <span x-text="step === {{ $lastIndex }} ? 'Lihat Result' : 'Seterusnya'">Simpan keperluan saya</span> @include('builder.partials.icon', ['name' => 'arrow-right'])
                    </button>
                </div>
            </div>
        </form>

        {{-- RINGKASAN & PENGESAHAN --}}
        <section class="bw-wrap bw-review" x-show="step > {{ $lastIndex }}" @if ($wizard['step'] <= $lastIndex) x-cloak @endif aria-labelledby="bw-review-title">
            <div class="bw-card bw-review-card">
                <header class="bw-step-head">
                    <span class="bw-step-icon">@include('builder.partials.icon', ['name' => 'check-badge'])</span>
                    <div><p class="bw-step-count">Semua langkah selesai</p><h2 id="bw-review-title" tabindex="-1" x-ref="review">Ringkasan keperluan</h2><p class="bw-step-sub">Semak jawapan anda. Klik “Ubah” untuk kembali ke mana-mana langkah.</p></div>
                </header>
                <dl class="bw-summary">
                    <template x-for="(s, i) in steps" :key="s.key">
                        <div><dt x-text="(i + 1) + '. ' + s.label"></dt><dd x-text="summary(i) || '—'"></dd><button type="button" class="bw-link" @click="goTo(i)">@include('builder.partials.icon', ['name' => 'edit']) Ubah</button></div>
                    </template>
                </dl>
                <div class="bw-cost is-review" x-show="selectedPackage()" x-cloak>
                    <div class="bw-cost-head"><p class="bw-kicker">Jumlah kos projek</p><strong x-text="rm(costTotal())"></strong></div>
                    <ul class="bw-cost-lines">
                        <li><span><b x-text="selectedPackage()?.name"></b> <small x-text="selectedPackage()?.label"></small></span><button type="button" class="bw-link" @click="changePackage()">Tukar pakej</button></li>
                        <template x-for="a in selectedAddons()" :key="a.id"><li><span>Add-on: <b x-text="a.name"></b> <small x-text="a.label"></small></span><button type="button" class="bw-link is-remove" @click="removeAddon(a.id)">Buang</button></li></template>
                    </ul>
                    <p class="bw-muted bw-cost-note">Selepas Master Specification diluluskan, quotation dengan jumlah ini dijana terus untuk anda terima dan bayar.</p>
                </div>
            </div>

            @if ($identityVerified && ! $journey['specification'])
                <div class="bw-card bw-verify">
                    <header class="bw-step-head">
                        <span class="bw-step-icon">@include('builder.partials.icon', ['name' => 'note'])</span>
                        <div><p class="bw-step-count">Keperluan disimpan</p><h2>Jana Master Specification</h2><p class="bw-step-sub">Semak ringkasan keperluan anda sebelum ia diluluskan.</p></div>
                    </header>
                    <a class="bw-btn bw-btn-primary" href="{{ route('specification.show') }}">Lihat Master Specification @include('builder.partials.icon', ['name' => 'arrow-right'])</a>
                </div>
            @endif
        </section>
    @endif
</div>

<script>
function builderWizard(cfg) {
    const answers = Object.assign({}, cfg.answers || {});
    Object.entries(cfg.questions).forEach(([code, q]) => {
        if (q.type === 'multiple_choice' && ! Array.isArray(answers[code])) answers[code] = answers[code] ? [answers[code]] : [];
        if (q.type !== 'multiple_choice' && answers[code] === undefined) answers[code] = '';
    });
    const last = cfg.steps.length - 1;
    const mocks = {
        'online-store': { links: ['Kedai', 'Koleksi', 'Promosi', 'Akaun'], button: 'Troli', title: 'Koleksi terbaik, terus ke pintu anda', cta: 'Beli Sekarang' },
        'promotion-landing-page': { links: ['Kelebihan', 'Harga', 'Testimoni', 'FAQ'], button: 'Hubungi', title: 'Tawaran istimewa untuk anda', cta: 'Tempah Sekarang' },
        'business-management-system': { links: ['Dashboard', 'Pelanggan', 'Laporan', 'Tetapan'], button: 'Log Masuk', title: 'Urus operasi bisnes dalam satu sistem', cta: 'Log Masuk' },
        'ai-chatbot-automation': { links: ['Ciri', 'Integrasi', 'Harga', 'Hubungi'], button: 'Daftar', title: 'Automasi kerja dengan bantuan AI', cta: 'Cuba Sekarang' },
        'upgrade-existing': { links: ['Utama', 'Servis', 'Tentang', 'Hubungi'], button: 'Daftar', title: 'Wajah baharu untuk website anda', cta: 'Mulakan Sekarang' },
    };
    const defaultMock = { links: ['Utama', 'Servis', 'Tentang', 'Hubungi'], button: 'Daftar', title: 'Reka bentuk moden untuk bisnes anda', cta: 'Mulakan Sekarang' };

    return {
        ...cfg, answers, errors: {}, uploadError: {}, uploading: null, busy: false, notice: '',
        packageId: cfg.packageId ? String(cfg.packageId) : '', addonIds: (cfg.addonIds || []).map(String),
        availablePackages() {
            const services = this.serviceMap[this.answers.project_type] || null;
            return services ? this.packageData.filter(p => services.includes(p.service) || p.id === String(this.packageId)) : this.packageData;
        },
        selectedPackage() { return this.packageData.find(p => p.id === String(this.packageId)) || null; },
        included() { return this.packageIncludes[this.selectedPackage()?.slug] || []; },
        functionsApply() { return this.answers.project_type === 'company-website'; },
        addonFunction(a) { if (! this.functionsApply()) return null; const fns = Object.keys(this.functionAddons).filter(f => this.functionAddons[f] === a.slug); return fns.length ? fns : null; },
        addonIncluded(a) { const fns = Object.keys(this.functionAddons).filter(f => this.functionAddons[f] === a.slug); return fns.length > 0 && fns.some(f => this.included().includes(f)); },
        isAddonOn(a) { const fns = this.addonFunction(a); return fns ? fns.some(f => (this.answers.website_functions || []).includes(f)) : this.addonIds.includes(a.id); },
        toggleAddon(a) {
            const fns = this.addonFunction(a);
            if (fns) { const cur = this.answers.website_functions || []; this.answers.website_functions = this.isAddonOn(a) ? cur.filter(f => ! fns.includes(f)) : cur.concat([fns[0]]); }
            else this.addonIds = this.addonIds.includes(a.id) ? this.addonIds.filter(x => x !== a.id) : this.addonIds.concat([a.id]);
        },
        fnBadge(code) { if (! this.selectedPackage()) return ''; if (this.included().includes(code)) return 'Termasuk'; const slug = this.functionAddons[code]; const a = slug && this.findAddon(slug); return a ? '+' + a.label : ''; },
        ownAddons() { return this.selectedPackage()?.addons || []; },
        findAddon(slug) { return this.ownAddons().find(x => x.slug === slug) || this.addonCatalog.find(x => x.slug === slug) || null; },
        pkgAddons() {
            const own = this.ownAddons();
            if (! this.functionsApply()) return own;
            return own.concat(this.addonCatalog.filter(c => ! own.some(o => o.slug === c.slug) && ! this.addonIncluded(c) && this.isAddonOn(c)));
        },
        explicitAddonIds() { const mapped = Object.values(this.functionAddons); const own = this.ownAddons(); const ids = this.addonIds.filter(id => own.some(o => o.id === id)); return this.functionsApply() ? ids.filter(id => ! mapped.includes(own.find(x => x.id === id)?.slug)) : ids; },
        fnBadgeClass(code) { return this.fnBadge(code) === 'Termasuk' ? 'is-included' : 'is-paid'; },
        selectedAddons() { return this.pkgAddons().filter(a => ! this.addonIncluded(a) && this.isAddonOn(a)); },
        costTotal() { return (this.selectedPackage()?.cents || 0) + this.selectedAddons().reduce((t, a) => t + (a.monthly ? 0 : (a.cents || 0)), 0); },
        rm(cents) { return 'RM' + (cents / 100).toLocaleString('en-MY', { minimumFractionDigits: 0, maximumFractionDigits: 2 }); },
        removeAddon(id) { const a = this.pkgAddons().find(x => x.id === id); if (a && this.isAddonOn(a)) this.toggleAddon(a); this.request(Math.max(this.reached, this.step)); },
        changePackage() { this.goTo(0); this.$nextTick(() => document.getElementById(this.isDirect ? 'service_package_id' : 'service_package_id_g')?.focus()); },
        reached: Math.max(cfg.step, 0),
        init() { this.$nextTick(() => this.scrollFlow()); },
        current() { return this.steps[Math.min(this.step, last)]; },
        visible(code) {
            const q = this.questions[code]; if (! q || ! q.condition) return true;
            return Object.entries(q.condition).every(([dep, expected]) => {
                const v = this.answers[dep]; const actual = (Array.isArray(v) ? v : [v]).filter(Boolean);
                return actual.length && (expected.includes('*') || actual.some(x => expected.includes(x)));
            });
        },
        isOn(code, opt) { const v = this.answers[code]; return Array.isArray(v) ? v.includes(opt) : v === opt; },
        optionLabel(code, value) { return (this.questions[code]?.options || {})[value] || ''; },
        done(i) { return i < this.reached && i !== this.step; },
        canVisit(i) { return i <= this.reached; },
        flowClass(i) { return { 'is-done': this.done(i), 'is-active': this.step === i, 'is-line-done': i <= this.reached && i > 0 && (this.done(i - 1)) }; },
        goTo(i) { if (i > this.reached) return; this.step = i; this.errors = {}; this.focusStep(); },
        focusStep() { this.$nextTick(() => { const h = this.step > last ? this.$refs.review : this.$refs['h' + this.step]; h && h.focus({ preventScroll: true }); this.scrollFlow(); const top = this.$refs.flow.getBoundingClientRect().top + window.scrollY - 96; if (window.scrollY > top) window.scrollTo({ top, behavior: 'smooth' }); }); },
        scrollFlow() { const el = this.$refs.flow?.children[Math.min(this.step, last)]; if (el && this.$refs.flow.scrollWidth > this.$refs.flow.clientWidth) this.$refs.flow.scrollTo({ left: el.offsetLeft - this.$refs.flow.clientWidth / 2 + el.clientWidth / 2, behavior: 'smooth' }); },
        validate() {
            const e = {}; const s = this.steps[this.step];
            if (s.key === 'model') {
                if (! this.isDirect && ! this.answers.project_type) e['answers.project_type'] = 'Sila pilih jenis projek.';
                else if (! this.packageId) e.service_package_id = 'Sila pilih pakej.';
            }
            s.codes.forEach(code => {
                const q = this.questions[code], v = this.answers[code];
                if (! this.visible(code)) return;
                if (q.required && ! (this.isDirect && code === 'project_type') && (Array.isArray(v) ? ! v.length : ! String(v || '').trim())) e['answers.' + code] = q.type.includes('choice') ? 'Sila pilih jawapan.' : 'Sila isi jawapan.';
                if (q.type === 'url' && v && ! /^https?:\/\/\S+\.\S+/i.test(v)) e['answers.' + code] = 'Sila masukkan URL yang sah (bermula dengan https://).';
                if (code === 'domain_name' && v && ! /^(https?:\/\/)?([a-z0-9-]+\.)+[a-z]{2,}\/?$/i.test(v.trim())) e['answers.' + code] = 'Sila masukkan nama domain yang sah, contoh namabisnes.com.my.';
            });
            this.errors = e; return ! Object.keys(e).length;
        },
        payload(nextStep) {
            const a = {}; Object.keys(this.questions).forEach(code => { a[code] = this.answers[code]; });
            return { answers: a, service_package_id: this.packageId || null, addon_ids: this.explicitAddonIds(), step: nextStep };
        },
        async request(nextStep) {
            this.busy = true;
            try {
                const r = await fetch(this.urls.save, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(this.payload(nextStep)), credentials: 'same-origin' });
                const data = await r.json().catch(() => ({}));
                if (r.status === 422) { this.showServerErrors(data.errors || {}); return false; }
                if (! r.ok) { this.notice = 'Tidak dapat disimpan. Sila cuba lagi.'; return false; }
                this.notice = 'Disimpan ' + (data.saved_at || ''); setTimeout(() => { this.notice = ''; }, 4000); return true;
            } catch (err) { this.notice = 'Tiada sambungan. Sila cuba lagi.'; return false; } finally { this.busy = false; }
        },
        showServerErrors(errs) {
            const e = {}; Object.entries(errs).forEach(([k, v]) => { e[k] = Array.isArray(v) ? v[0] : v; }); this.errors = e;
            const key = Object.keys(e)[0] || ''; const code = key.replace('answers.', '');
            const idx = key === 'service_package_id' ? 0 : (key.startsWith('addon_ids') ? this.steps.findIndex(s => s.key === 'addon') : this.steps.findIndex(s => s.codes.includes(code)));
            if (idx >= 0 && idx !== this.step) { this.step = idx; this.focusStep(); }
        },
        async save() { if (! this.validate()) return; await this.request(Math.max(this.reached, this.step)); },
        async next() {
            if (! this.validate()) return;
            const target = this.step + 1; const reach = Math.max(this.reached, target);
            if (await this.request(reach)) { this.reached = reach; this.step = target; this.focusStep(); }
        },
        back() { if (this.step > 0) { this.step--; this.errors = {}; this.focusStep(); } },
        filesOf(kind) { return this.files.filter(f => f.kind === kind); },
        logoFile() { return this.files.find(f => f.kind === 'logo' && f.is_image) || null; },
        async upload(kind, list) {
            this.uploadError[kind] = '';
            for (const file of Array.from(list || [])) {
                if (file.size > 5 * 1024 * 1024) { this.uploadError[kind] = file.name + ': saiz maksimum 5MB.'; continue; }
                this.uploading = kind;
                const fd = new FormData(); fd.append('kind', kind); fd.append('file', file);
                try {
                    const r = await fetch(this.urls.files, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf }, body: fd, credentials: 'same-origin' });
                    const data = await r.json().catch(() => ({}));
                    if (r.ok) this.files.push(data); else this.uploadError[kind] = Object.values(data.errors || {})[0]?.[0] || 'Fail tidak dapat dimuat naik.';
                } catch (err) { this.uploadError[kind] = 'Tiada sambungan. Sila cuba lagi.'; }
            }
            this.uploading = null;
        },
        async removeFile(f) {
            const r = await fetch(f.url, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf }, credentials: 'same-origin' });
            if (r.ok) this.files = this.files.filter(x => x.id !== f.id);
        },
        validHex(v) { return /^#[0-9a-f]{6}$/i.test(String(v || '').trim()); },
        mix(hex, pct) { const n = parseInt(hex.slice(1), 16); const c = [n >> 16, (n >> 8) & 255, n & 255].map(x => Math.round(x + (255 - x) * pct)); return '#' + c.map(x => x.toString(16).padStart(2, '0')).join(''); },
        palette() {
            const code = this.answers.colour_preference;
            if (code === 'lain-lain' && this.validHex(this.answers.colour_custom)) { const h = this.answers.colour_custom.trim(); return [h, this.mix(h, .25), this.mix(h, .5), this.mix(h, .8)]; }
            return (this.colours[code] || this.colours.biru).swatch;
        },
        colourDesc() { return (this.colours[this.answers.colour_preference] || {}).desc || 'Pilih warna tema di sebelah.'; },
        accentVars() { const p = this.palette(); return `--acc:${p[0]};--acc-2:${p[1]};--acc-3:${p[2]};--acc-soft:${this.mix(p[0], .92)};--acc-line:${this.mix(p[0], .7)}`; },
        mock() { return mocks[this.answers.project_type] || defaultMock; },
        mockDomain() { const d = String(this.answers.domain_name || '').trim().replace(/^https?:\/\//i, '').replace(/\/$/, ''); return d || 'namabisnes.com.my'; },
        previewPhoto() {
            const imgs = this.answers.content_images || [];
            for (const k of ['product-photos', 'people-photos', 'location-photos']) if (imgs.includes(k)) return this.preview[k];
            return this.preview[this.answers.project_type] || this.preview.default;
        },
        summary(i) {
            const s = this.steps[i]; if (! s) return ''; const parts = [];
            if (s.key === 'model' && this.packageId) parts.push(this.packages[this.packageId] || '');
            if (s.key === 'addon') parts.push(this.selectedAddons().map(a => a.name + ' (' + a.label + ')').join(', ') || 'Tiada add-on');
            s.codes.forEach(code => {
                if (! this.visible(code)) return; const q = this.questions[code], v = this.answers[code];
                if (Array.isArray(v)) { if (v.length) parts.push(v.map(x => q.options[x] || x).join(', ')); }
                else if (v) parts.push(q.options && q.options[v] ? q.options[v] : String(v));
            });
            if (s.key === 'logo' && this.filesOf('logo').length) parts.push(this.filesOf('logo').length + ' fail logo');
            if (s.key === 'rujukan' && this.filesOf('reference').length) parts.push(this.filesOf('reference').length + ' fail rujukan');
            const text = parts.filter(Boolean).join(' · '); return text.length > 220 ? text.slice(0, 217) + '…' : text;
        },
    };
}
</script>
@endsection
