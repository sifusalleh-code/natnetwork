<?php

namespace App\Engines\Communication\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationSetting extends Model
{
    protected $fillable = ['email_enabled', 'resend_api_key', 'from_email', 'from_name'];
    protected $hidden = ['resend_api_key'];

    protected function casts(): array
    {
        return ['email_enabled' => 'boolean', 'resend_api_key' => 'encrypted'];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['email_enabled' => false, 'from_name' => 'NatNetwork Synergy']);
    }

    public function resendReady(): bool
    {
        return $this->email_enabled && filled($this->resend_api_key) && filled($this->from_email);
    }

    public function fromHeader(): string
    {
        return filled($this->from_name) ? sprintf('%s <%s>', str_replace(['<', '>', '"'], '', $this->from_name), $this->from_email) : (string) $this->from_email;
    }
}
