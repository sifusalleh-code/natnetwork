<?php

namespace App\Http\Controllers;

use App\Engines\Affiliate\Services\AffiliateTrackingService;
use App\Engines\Identity\Services\EmailOtpService;
use App\Engines\Identity\Services\OtpRateLimited;
use App\Engines\Identity\Services\OtpVerificationFailed;
use App\Engines\Pricing\Models\Service;
use App\Engines\Pricing\Models\ServicePackage;
use App\Engines\Sales\Models\BuilderFile;
use App\Engines\Sales\Services\BuilderFileService;
use App\Engines\Sales\Services\BuilderSessionService;
use App\Engines\Sales\Services\StartProjectJourneyService;
use App\Engines\Sales\Services\StartProjectResetService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProjectBuilderController extends Controller
{
    public function show(Request $request, BuilderSessionService $builder, StartProjectJourneyService $journeys): View
    {
        $builder->resume($request);
        $session = $builder->currentOrNew($request);
        $client = $request->user('client');
        if ($client && $session->exists && $session->entry_path && ! $session->user_id) {
            $builder->linkClient($session, $client); // emel akaun sudah disahkan: tiada kod baharu
        }
        $verified = $builder->isVerifiedFor($session, $client);
        $stepCount = count(config('builder.steps'));
        $selectedPackage = null;

        if (! $session->entry_path && $request->filled('package')) {
            $selectedPackage = ServicePackage::query()->where('slug', (string) $request->string('package'))->where('is_active', true)->first();
        }

        return view('builder.start', [
            'builderSession' => $session,
            'questions' => $builder->questions(),
            'answers' => $builder->answers($session),
            'services' => Service::query()->where('is_active', true)->with(['packages' => fn ($query) => $query->where('is_active', true), 'packages.packageAddons.addon'])->orderBy('display_order')->get(),
            'selectedPackage' => $selectedPackage,
            'builderFiles' => $session->exists ? $session->files()->orderBy('id')->get()->map(fn (BuilderFile $file) => app(BuilderFileService::class)->toArray($file))->values() : collect(),
            'builderStep' => (int) ($session->current_step ?? $request->session()->get('natnetwork_builder_step', 0)),
            'identityVerified' => $verified,
            // Harga standard add-on bagi fungsi berbayar yang tiada dalam senarai add-on pakej.
            'addons' => \App\Engines\Pricing\Models\Addon::query()->where('is_active', true)->whereIn('slug', array_values(config('builder.function_addons', [])))->orderBy('display_order')->get(),
            'journey' => $verified ? $journeys->for($session, $stepCount) : ['steps' => [], 'specification' => null, 'started' => false, 'paymentFailed' => false, 'canReset' => false],
        ]);
    }

    public function chooseEntry(Request $request, BuilderSessionService $builder): RedirectResponse
    {
        $data = $request->validate([
            'entry_path' => ['required', Rule::in(['DIRECT_SELECTION', 'GUIDED'])],
            'package' => ['nullable', 'string', 'max:255'],
        ]);
        $session = $builder->current($request);
        $package = null;

        if ($data['entry_path'] === 'DIRECT_SELECTION' && filled($data['package'] ?? null)) {
            $package = ServicePackage::query()->where('slug', $data['package'])->where('is_active', true)->first();
            if (! $package) {
                throw ValidationException::withMessages(['package' => ['Pakej pilihan tidak lagi tersedia. Sila pilih pakej yang lain.']]);
            }
        }

        $session->update([
            'entry_path' => $data['entry_path'],
            'service_package_id' => $data['entry_path'] === 'GUIDED' ? null : ($package?->id ?? $session->service_package_id),
            // Pakej sudah disahkan terus daripada halaman Services (bukan pilih dalam wizard):
            // langkau langkah "Model" (jenis projek + pilih pakej), terus ke langkah "Gaya".
            'current_step' => $package ? 1 : $session->current_step,
        ]);
        if ($client = $request->user('client')) {
            $builder->linkClient($session, $client);
        }

        return redirect()->route('builder.start');
    }

    public function save(Request $request, BuilderSessionService $builder): RedirectResponse|JsonResponse
    {
        $session = $builder->current($request);
        $this->ensureIdentity($request, $builder, $session);
        $data = $request->validate([
            // Pakej wajib bagi kedua-dua laluan: kos dipapar dari awal (pakej + add-on).
            'service_package_id' => ['required', 'integer', Rule::exists('service_packages', 'id')->where('is_active', true)],
            'addon_ids' => ['nullable', 'array', 'max:20'],
            'addon_ids.*' => ['integer', Rule::exists('addons', 'id')->where('is_active', true)],
            'answers' => ['nullable', 'array'],
            'step' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);
        $session->update([
            'service_package_id' => $data['service_package_id'],
            'addon_ids' => array_key_exists('addon_ids', $data) ? array_values(array_unique(array_map('intval', $data['addon_ids'] ?? []))) : $session->addon_ids,
        ]);
        $builder->saveAnswers($session, $data['answers'] ?? []);
        if (array_key_exists('step', $data) && $data['step'] !== null) {
            $request->session()->put('natnetwork_builder_step', (int) $data['step']);
            $session->forceFill(['current_step' => (int) $data['step']])->save(); // autosave kemajuan (disambung dalam 30 hari)
        }

        if ($request->expectsJson()) {
            $estimate = app(\App\Engines\Pricing\Services\PriceEstimateService::class)->estimate($session->servicePackage()->first(), $builder->effectiveAddonIds($session->fresh()));

            return response()->json(['saved' => true, 'message' => 'Keperluan anda telah disimpan.', 'saved_at' => now()->format('H:i'), 'total_cents' => $estimate['total_cents']]);
        }

        return redirect()->route('builder.start')->with('builder_status', 'Keperluan anda telah disimpan.');
    }

    public function uploadFile(Request $request, BuilderSessionService $builder, BuilderFileService $files): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(BuilderFile::KINDS))],
            'file' => ['required', 'file', 'max:'.BuilderFileService::MAX_KB, 'mimes:jpg,jpeg,png,webp,pdf'],
        ], ['file.max' => 'Saiz fail maksimum 5MB.', 'file.mimes' => 'Gunakan JPG, PNG, WebP atau PDF.']);
        $session = $builder->current($request);
        abort_unless($session->entry_path && $builder->isVerifiedFor($session, $request->user('client')), 422);

        return response()->json($files->toArray($files->store($session, $data['kind'], $request->file('file'))), 201);
    }

    public function showFile(Request $request, BuilderFile $file, BuilderSessionService $builder): Response
    {
        $this->authorizeFile($request, $file, $builder);

        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($file->path), [
            'Content-Type' => $file->mime,
            'Content-Disposition' => ($file->isImage() ? 'inline' : 'attachment').'; filename="'.addcslashes($file->original_name, '"\\').'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    public function deleteFile(Request $request, BuilderFile $file, BuilderSessionService $builder, BuilderFileService $files): JsonResponse
    {
        $this->authorizeFile($request, $file, $builder);
        $files->delete($file);

        return response()->json(['deleted' => true]);
    }

    private function authorizeFile(Request $request, BuilderFile $file, BuilderSessionService $builder): void
    {
        $session = $builder->currentOrNew($request);
        $ownsSession = $session->exists && $session->id === $file->builder_session_id;
        $ownsAccount = auth('client')->check() && $file->session?->user_id === auth('client')->id();
        abort_unless($ownsSession || $ownsAccount, 404);
    }

    public function requestVerification(Request $request, BuilderSessionService $builder, EmailOtpService $otp): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
        ]);
        $session = $builder->current($request);
        $session->update(['contact_name' => $data['name'], 'contact_company' => $data['company'], 'contact_email' => mb_strtolower(trim($data['email'])), 'contact_phone' => $data['phone']]);

        // Emel akaun yang sudah disahkan dan sedang log masuk: tiada pengesahan semula.
        $client = $request->user('client');
        if ($client && mb_strtolower((string) $client->email) === $session->contact_email) {
            $builder->linkClient($session, $client);

            return redirect()->route('builder.start')->with('builder_status', 'Maklumat anda disimpan. Soal jawab boleh bermula.');
        }

        try {
            $otp->issue($session->contact_email, (string) $request->ip());
        } catch (OtpRateLimited $exception) {
            // Kod sebelumnya masih sah: kekalkan ruang kod supaya pengguna boleh terus memasukkannya.
            return redirect()->route('builder.start')->withInput()->withErrors(['email' => "Sila tunggu {$exception->retryAfterSeconds} saat sebelum meminta kod baharu."])->with('builder_verification_pending', true);
        }

        return redirect()->route('builder.start')->with('builder_verification_pending', true)->with('builder_status', 'Kod pengesahan telah dihantar ke email anda.');
    }

    public function verify(Request $request, BuilderSessionService $builder, EmailOtpService $otp, AffiliateTrackingService $affiliates): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $session = $builder->current($request);

        if (! $session->contact_email || ! $session->contact_name || ! $session->contact_phone) {
            throw ValidationException::withMessages(['code' => ['Sila isi maklumat hubungan terlebih dahulu.']]);
        }

        try {
            $challenge = $otp->consume($session->contact_email, $data['code']);
        } catch (OtpVerificationFailed $exception) {
            throw ValidationException::withMessages(['code' => [$exception->getMessage()]]);
        }

        $user = DB::transaction(function () use ($session, $challenge, $affiliates, $request): User {
            $lockedSession = $session->newQuery()->lockForUpdate()->findOrFail($session->id);
            $user = User::query()->firstOrCreate(['email' => $lockedSession->contact_email], [
                'name' => $lockedSession->contact_name,
                'company' => $lockedSession->contact_company,
                'phone' => $lockedSession->contact_phone,
                'role' => User::ROLE_CUSTOMER,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $lockedSession->update(['user_id' => $user->id, 'email_verified_at' => now()]);
            $challenge->update(['user_id' => $user->id]);

            if ($user->wasRecentlyCreated) {
                $affiliates->linkNewCustomer($user, $request);
            }

            return $user;
        });

        // Emel disahkan sekali: log masuk diingati 30 hari pada peranti ini (tiada kod semula).
        Auth::guard('client')->setRememberDuration(BuilderSessionService::RESUME_DAYS * 24 * 60);
        Auth::guard('client')->login($user, true);
        $request->session()->regenerate();

        // Pelanggan sama datang semula (peranti lain) dalam 30 hari: buka semula Start Project tersimpan jika sesi ini masih kosong.
        $older = $builder->latestFor($user, $session->id);
        if ($older && ! $session->answers()->exists() && ! $session->files()->exists()) {
            $session->forceFill(['reset_at' => now()])->save();
            $request->session()->put(BuilderSessionService::SESSION_KEY, $older->id);
            $request->session()->forget('natnetwork_builder_step');

            return redirect()->route('builder.start')->with('builder_status', 'Emel anda telah disahkan. Start Project anda yang tersimpan telah dibuka semula.');
        }

        return redirect()->route('builder.start')->with('builder_status', 'Emel anda telah disahkan. Soal jawab Start Project boleh bermula.');
    }

    /** Reset Start Project (contoh: selepas bayaran gagal). Maklumat asas dikekalkan untuk Start Project baharu. */
    public function reset(Request $request, BuilderSessionService $builder, StartProjectResetService $resets): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Sila sahkan anda mahu reset Start Project.']);
        $client = $request->user('client');
        $session = $builder->currentOrNew($request);
        abort_unless($client && $builder->isVerifiedFor($session, $client), 403);

        $resets->reset($session, $client);

        $request->session()->forget([BuilderSessionService::SESSION_KEY, 'natnetwork_builder_step']);
        $builder->linkClient($builder->current($request), $client);

        return redirect()->route('builder.start')->with('builder_status', 'Start Project telah di-reset. Bil lama dibatalkan dan anda boleh mula semula.');
    }

    /** Projek terdahulu telah disahkan (Order): mula Start Project baharu tanpa menjejaskan rekod lama. */
    public function newProject(Request $request, BuilderSessionService $builder): RedirectResponse
    {
        $client = $request->user('client');
        abort_unless($client, 403);
        $request->session()->forget([BuilderSessionService::SESSION_KEY, 'natnetwork_builder_step']);
        $builder->linkClient($builder->current($request), $client);

        return redirect()->route('builder.start');
    }

    private function ensureIdentity(Request $request, BuilderSessionService $builder, $session): void
    {
        if (! $builder->isVerifiedFor($session, $request->user('client'))) {
            throw ValidationException::withMessages(['identity' => ['Sila isi maklumat anda (nama, no. telefon dan emel) sebelum soal jawab.']]);
        }
    }
}
