<?php

namespace App\Console\Commands;

use App\Engines\Identity\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {name}';

    protected $description = 'Cipta akaun Admin (password diminta secara interaktif; 2FA wajib didaftarkan semasa log masuk pertama)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->secret('Password (sekurang-kurangnya 12 aksara)');
        $confirm = (string) $this->secret('Sahkan password');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $confirm],
            ['email' => ['required', 'email:filter', 'unique:admins,email'], 'password' => ['required', 'string', 'min:12', 'confirmed']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        Admin::query()->create(['name' => (string) $this->argument('name'), 'email' => $email, 'password' => $password]);
        $this->info('Admin dicipta. Log masuk di /admin/login dan daftarkan 2FA.');

        return self::SUCCESS;
    }
}
