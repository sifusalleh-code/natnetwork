<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Validator ringkas bagi struktur fasa AGENTS.md §19 (docs/PHASE_TRACKER.md).
 * Ini BUKAN pengganti kelulusan Owner — ia hanya menyemak isyarat teknikal asas
 * (migrasi tertunggak, engine wujud) supaya percanggahan mudah dikesan awal.
 */
class PhaseStatus extends Command
{
    protected $signature = 'phase:status';

    protected $description = 'Semakan validator ringkas struktur fasa pembangunan (AGENTS.md §19 / docs/PHASE_TRACKER.md)';

    /** Engine yang diwajibkan AGENTS.md §3 + engine tambahan yang telah diluluskan (Affiliate, Partnership). */
    private const EXPECTED_ENGINES = [
        'Identity', 'Sales', 'Pricing', 'Scheduling', 'Billing', 'Project', 'ProjectContent',
        'Communication', 'Cms', 'Audit', 'Analytics', 'Affiliate', 'Partnership',
    ];

    public function handle(): int
    {
        $this->components->info('Validator Fasa NatNetwork — semakan teknikal asas sahaja.');
        $this->newLine();

        $ok = true;
        $ok = $this->checkMigrations() && $ok;
        $ok = $this->checkEngines() && $ok;
        $this->checkSubscriptionEngine();

        $this->newLine();
        if ($ok) {
            $this->components->info('Semakan asas lulus. Ini TIDAK bermakna sesuatu fasa selesai — rujuk docs/PHASE_TRACKER.md dan AGENTS.md §15/§19 untuk kelulusan penuh (function/security/regression test + laporan + kelulusan Owner).');
        } else {
            $this->components->warn('Terdapat isu yang perlu disemak sebelum fasa dianggap stabil.');
        }

        return self::SUCCESS;
    }

    private function checkMigrations(): bool
    {
        $files = collect(File::files(database_path('migrations')))
            ->map(fn ($f) => pathinfo($f->getFilename(), PATHINFO_FILENAME))
            ->sort()->values();

        try {
            $hasTable = DB::getSchemaBuilder()->hasTable('migrations');
        } catch (\Throwable) {
            $this->components->warn('Pangkalan data belum wujud/disambung — jalankan `php artisan migrate` dahulu.');

            return false;
        }

        if (! $hasTable) {
            $this->components->warn('Jadual migrations tiada — pangkalan data belum dimigrasikan (`php artisan migrate`).');

            return false;
        }

        $ran = DB::table('migrations')->pluck('migration');
        $pending = $files->diff($ran);

        if ($pending->isEmpty()) {
            $this->components->twoColumnDetail('Migrasi', "<fg=green>Terkini</> ({$files->count()} fail)");

            return true;
        }

        $this->components->twoColumnDetail('Migrasi', '<fg=red>Tertunggak</> ('.$pending->count().' fail)');
        foreach ($pending->take(10) as $name) {
            $this->line("  - {$name}");
        }

        return false;
    }

    private function checkEngines(): bool
    {
        $missing = collect(self::EXPECTED_ENGINES)->reject(fn ($engine) => File::isDirectory(app_path("Engines/{$engine}")));

        if ($missing->isEmpty()) {
            $this->components->twoColumnDetail('Engine (app/Engines/*)', '<fg=green>Semua '.count(self::EXPECTED_ENGINES).' wujud</>');

            return true;
        }

        $this->components->twoColumnDetail('Engine (app/Engines/*)', '<fg=red>Hilang: '.$missing->implode(', ').'</>');

        return false;
    }

    /** Fasa 8 (Recurring Services) — Subscription Engine masih belum dibina; ini peringatan, bukan kegagalan. */
    private function checkSubscriptionEngine(): void
    {
        $exists = File::isDirectory(app_path('Engines/Subscription'));
        $this->components->twoColumnDetail('Subscription Engine (Fasa 8)', $exists ? '<fg=green>Wujud</>' : '<fg=yellow>Belum dibina — lihat docs/PHASE_TRACKER.md</>');
    }
}
