<?php

namespace App\Engines\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    public const MODE_SANDBOX = 'SANDBOX';
    public const MODE_PRODUCTION = 'PRODUCTION';
    public const MODES = [self::MODE_SANDBOX, self::MODE_PRODUCTION];

    protected $fillable = [
        'active_mode', 'sandbox_api_key', 'sandbox_collection_id', 'sandbox_x_signature_key',
        'production_api_key', 'production_collection_id', 'production_x_signature_key',
        'full_payment_reward_enabled', 'full_payment_reward_type', 'full_payment_reward_value', 'full_payment_reward_addon_id',
    ];

    public const REWARD_TYPES = ['percent' => 'Diskaun peratus (%)', 'fixed' => 'Diskaun tetap (RM)', 'addon' => 'Add-on percuma'];

    protected $hidden = ['sandbox_api_key', 'sandbox_x_signature_key', 'production_api_key', 'production_x_signature_key'];

    protected function casts(): array
    {
        return [
            'sandbox_api_key' => 'encrypted',
            'sandbox_x_signature_key' => 'encrypted',
            'production_api_key' => 'encrypted',
            'production_x_signature_key' => 'encrypted',
            'full_payment_reward_enabled' => 'boolean',
            'full_payment_reward_value' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['active_mode' => self::MODE_SANDBOX]);
    }

    public function isSandbox(): bool
    {
        return $this->active_mode !== self::MODE_PRODUCTION;
    }

    /** @return array{api_key: ?string, collection_id: ?string, x_signature_key: ?string} */
    public function credentials(string $mode): array
    {
        $prefix = $mode === self::MODE_PRODUCTION ? 'production' : 'sandbox';

        return [
            'api_key' => $this->{$prefix.'_api_key'},
            'collection_id' => $this->{$prefix.'_collection_id'},
            'x_signature_key' => $this->{$prefix.'_x_signature_key'},
        ];
    }

    public function hasCredentials(string $mode): bool
    {
        return ! in_array(null, array_map(fn ($v) => filled($v) ? $v : null, $this->credentials($mode)), true);
    }

    public static function mask(?string $secret): ?string
    {
        return filled($secret) ? '••••'.substr($secret, -4) : null;
    }
}
