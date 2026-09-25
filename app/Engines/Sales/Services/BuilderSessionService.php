<?php

namespace App\Engines\Sales\Services;

use App\Engines\Sales\Models\BuilderAnswer;
use App\Engines\Sales\Models\BuilderQuestion;
use App\Engines\Sales\Models\BuilderSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuilderSessionService
{
    /** Start Project disimpan dan boleh disambung dalam tempoh ini (dikira dari aktiviti terakhir). */
    public const RESUME_DAYS = 30;

    public const SESSION_KEY = 'natnetwork_builder_session_id';

    public function current(Request $request): BuilderSession
    {
        $session = BuilderSession::query()->find($request->session()->get(self::SESSION_KEY));

        if (! $session || ! $this->isResumable($session)) {
            $session = BuilderSession::query()->create();
            $request->session()->put(self::SESSION_KEY, $session->id);
        }

        return $session;
    }

    public function currentOrNew(Request $request): BuilderSession
    {
        $session = BuilderSession::query()->find($request->session()->get(self::SESSION_KEY));

        return $session && $this->isResumable($session) ? $session : new BuilderSession();
    }

    public function isResumable(BuilderSession $session): bool
    {
        return $session->reset_at === null && $session->updated_at?->greaterThanOrEqualTo(now()->subDays(self::RESUME_DAYS)) !== false;
    }

    /** Start Project terkini pelanggan yang masih boleh disambung (30 hari, belum di-reset). */
    public function latestFor(User $customer, ?string $exceptId = null): ?BuilderSession
    {
        return BuilderSession::query()->where('user_id', $customer->id)->whereNull('reset_at')
            ->where('updated_at', '>=', now()->subDays(self::RESUME_DAYS))
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->latest('updated_at')->first();
    }

    /**
     * Pelanggan log masuk kembali ke Mula Projek: buka semula Start Project mereka (dalam 30 hari) jika sesi pelayar
     * tiada, telah tamat, atau milik akaun lain.
     */
    public function resume(Request $request): void
    {
        $session = BuilderSession::query()->find($request->session()->get(self::SESSION_KEY));
        if ($session && ! $this->isResumable($session)) {
            $request->session()->forget([self::SESSION_KEY, 'natnetwork_builder_step']);
            $session = null;
        }
        $client = $request->user('client');
        if (! $client) {
            return;
        }
        $foreign = $session && $session->user_id && $session->user_id !== $client->id;
        $blank = $session && ! $session->user_id && ! $session->entry_path;
        if (! $session || $foreign || $blank) {
            $latest = $this->latestFor($client);
            if ($latest) {
                $request->session()->put(self::SESSION_KEY, $latest->id);
                $request->session()->forget('natnetwork_builder_step');
            } elseif ($foreign) {
                $request->session()->forget([self::SESSION_KEY, 'natnetwork_builder_step']);
            }
        }
    }

    /** Pautkan Start Project kepada akaun pelanggan yang telah disahkan (tiada kod pengesahan baharu diperlukan). */
    public function linkClient(BuilderSession $session, User $customer): void
    {
        $session->forceFill([
            'user_id' => $customer->id,
            'contact_name' => $session->contact_name ?: $customer->name,
            'contact_email' => mb_strtolower((string) $customer->email),
            'contact_phone' => $session->contact_phone ?: $customer->phone,
            'contact_company' => $session->contact_company ?: $customer->company,
            'email_verified_at' => $session->email_verified_at ?? now(),
        ])->save();
    }

    /**
     * Add-on berkesan = add-on dipilih terus (yang ditawarkan oleh pakej) + fungsi berbayar dalam soalan "Fungsi"
     * (website syarikat) yang tidak termasuk dalam pakej. Fungsi yang termasuk dalam pakej tidak dicaj.
     *
     * @return list<int>
     */
    public function effectiveAddonIds(BuilderSession $session): array
    {
        $package = $session->servicePackage()->first();
        $included = config('builder.package_includes.'.($package?->slug ?? ''), []);
        $answers = $this->answers($session);
        $functions = ($answers['project_type'] ?? null) === 'company-website' ? (array) ($answers['website_functions'] ?? []) : [];
        $map = config('builder.function_addons', []);
        $slugs = collect($functions)->reject(fn ($f) => in_array($f, $included, true))->map(fn ($f) => $map[$f] ?? null)->filter()->unique()->values()->all();
        $derived = $slugs ? \App\Engines\Pricing\Models\Addon::query()->whereIn('slug', $slugs)->where('is_active', true)->pluck('id')->all() : [];
        // Add-on yang dipetakan kepada fungsi yang termasuk dalam pakej tidak dicaj walaupun dipilih terus.
        $includedAddonSlugs = collect($included)->map(fn ($f) => $map[$f] ?? null)->filter()->all();
        $includedIds = $includedAddonSlugs ? \App\Engines\Pricing\Models\Addon::query()->whereIn('slug', $includedAddonSlugs)->pluck('id')->all() : [];
        // Website syarikat: add-on yang dipetakan kepada fungsi hanya dikira melalui soalan Fungsi (satu sumber).
        $mappedIds = $functions !== [] || ($answers['project_type'] ?? null) === 'company-website'
            ? \App\Engines\Pricing\Models\Addon::query()->whereIn('slug', array_values($map))->pluck('id')->all() : [];
        // Add-on dipilih terus mestilah ditawarkan oleh pakej semasa (add-on pakej lama digugurkan selepas tukar pakej).
        $offeredIds = $package ? \App\Engines\Pricing\Models\PackageAddon::query()->where('service_package_id', $package->id)->where('is_active', true)->pluck('addon_id')->all() : [];
        $explicit = array_intersect(array_diff(array_map('intval', $session->addon_ids ?? []), $includedIds, $mappedIds), $offeredIds);

        return array_values(array_unique(array_merge($explicit, array_map('intval', $derived))));
    }

    public function isVerifiedFor(BuilderSession $session, ?User $customer): bool
    {
        return $customer !== null && $session->exists && $session->user_id === $customer->id && $session->email_verified_at !== null;
    }

    public function questions()
    {
        return BuilderQuestion::query()->where('is_active', true)->with('options')->orderBy('display_order')->get();
    }

    public function answers(BuilderSession $session): array
    {
        return $session->answers()->with('question')->get()->mapWithKeys(function (BuilderAnswer $answer): array {
            $value = match ($answer->question->question_type) {
                'url', 'textarea', 'text' => $answer->text_value,
                'single_choice' => $answer->value[0] ?? null,
                default => $answer->value ?? [],
            };

            return [$answer->question->code => $value];
        })->all();
    }

    public function saveAnswers(BuilderSession $session, array $submitted): void
    {
        $questions = $this->questions();
        $answers = $this->answers($session);

        foreach ($submitted as $code => $value) {
            $answers[$code] = $value;
        }

        $errors = [];
        $prepared = [];
        $visibleQuestionIds = [];

        foreach ($questions as $question) {
            if (! $this->isVisible($question->condition, $answers)) {
                continue;
            }

            $visibleQuestionIds[] = $question->id;
            $value = $answers[$question->code] ?? null;

            if (in_array($question->question_type, ['single_choice', 'multiple_choice'], true)) {
                $values = $question->question_type === 'multiple_choice' ? array_values(array_unique((array) $value)) : (filled($value) ? [(string) $value] : []);
                $allowed = $question->options->pluck('code')->all();
                if (! array_key_exists($question->code, $submitted)) {
                    // Jawapan tersimpan yang pilihannya telah dikemas kini: buang kod lama secara senyap.
                    $values = array_values(array_intersect($values, $allowed));
                }
                $isRequired = $question->is_required && ! ($session->entry_path === 'DIRECT_SELECTION' && $question->code === 'project_type');

                if (array_diff($values, $allowed)) {
                    $errors["answers.{$question->code}"] = 'Pilihan tidak sah.';
                }
                if ($isRequired && $values === []) {
                    $errors["answers.{$question->code}"] = 'Sila pilih jawapan.';
                }
                $prepared[$question->id] = ['value' => $values, 'text_value' => null];
                continue;
            }

            $text = is_string($value) ? trim($value) : '';
            if ($question->question_type === 'url' && $text !== '' && ! filter_var($text, FILTER_VALIDATE_URL)) {
                $errors["answers.{$question->code}"] = 'Sila masukkan URL yang sah.';
            }
            if (mb_strlen($text) > ($question->question_type === 'text' ? 255 : 5000)) {
                $errors["answers.{$question->code}"] = 'Jawapan terlalu panjang.';
            }
            if ($question->is_required && $text === '') {
                $errors["answers.{$question->code}"] = 'Sila isi jawapan.';
            }
            $prepared[$question->id] = ['value' => null, 'text_value' => $text ?: null];
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($session, $prepared, $visibleQuestionIds): void {
            $session->answers()->whereNotIn('builder_question_id', $visibleQuestionIds)->delete();
            foreach ($prepared as $questionId => $answer) {
                BuilderAnswer::query()->updateOrCreate(
                    ['builder_session_id' => $session->id, 'builder_question_id' => $questionId],
                    $answer,
                );
            }
            $session->touch();
        });
    }

    private function isVisible(?array $condition, array $answers): bool
    {
        if (! $condition) {
            return true;
        }

        foreach ($condition as $questionCode => $expected) {
            $actual = array_values(array_filter((array) ($answers[$questionCode] ?? null)));
            if ($actual === []) {
                return false;
            }
            if (! in_array('*', $expected, true) && ! array_intersect($actual, $expected)) {
                return false;
            }
        }

        return true;
    }
}
