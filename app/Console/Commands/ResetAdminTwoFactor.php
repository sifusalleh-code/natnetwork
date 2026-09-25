<?php

namespace App\Console\Commands;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Identity\Models\Admin;
use Illuminate\Console\Command;

class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:reset-2fa {email}';

    protected $description = 'Set semula 2FA admin (contoh: telefon hilang). Admin perlu mendaftar semula 2FA semasa log masuk.';

    public function handle(AuditLogger $audit): int
    {
        $admin = Admin::query()->where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();
        if (! $admin) {
            $this->error('Admin tidak dijumpai.');

            return self::FAILURE;
        }

        $admin->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_last_step' => null])->save();
        $audit->record('ADMIN_2FA_RESET', null, $admin, null, null, 'Dilaksanakan melalui arahan konsol.');
        $this->info('2FA telah diset semula.');

        return self::SUCCESS;
    }
}
