<?php

namespace App\Engines\Partnership\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerSetting extends Model
{
    protected $fillable = ['program_enabled', 'pool_percent', 'min_capital', 'max_total_capital'];
    protected function casts(): array { return ['program_enabled' => 'boolean', 'pool_percent' => 'decimal:2', 'min_capital' => 'decimal:2', 'max_total_capital' => 'decimal:2']; }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['program_enabled' => false, 'pool_percent' => 10, 'min_capital' => 5000, 'max_total_capital' => 100000]);
    }

    /** Modal terkumpul = modal AKTIF sahaja (bayaran disahkan), tidak termasuk sandbox. */
    public function collectedCapital(): float
    {
        return (float) PartnerCapital::query()->where('status', PartnerCapital::ACTIVE)->where('is_sandbox', false)->sum('amount');
    }

    /** Pendaftaran partner baharu ditutup sebaik modal terkumpul mencapai had kumpulan modal. */
    public function registrationFull(): bool
    {
        return (int) round($this->collectedCapital() * 100) >= (int) round((float) $this->max_total_capital * 100);
    }

    public function registrationOpen(): bool
    {
        return $this->program_enabled && ! $this->registrationFull();
    }
}
