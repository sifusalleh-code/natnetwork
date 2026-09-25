<?php

namespace App\Engines\Sales\Models;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Project\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Change Request selepas quotation diterima. Quotation asal tidak pernah diubah. */
class ChangeRequest extends Model
{
    public const SUBMITTED = 'SUBMITTED';
    public const IN_SCOPE = 'IN_SCOPE';            // Revision dalam skop — tiada caj
    public const QUOTED = 'QUOTED';                // Additional Work — menunggu kelulusan pelanggan
    public const APPROVED = 'APPROVED';            // Pelanggan lulus → invois 100%
    public const REJECTED_BY_CLIENT = 'REJECTED_BY_CLIENT';
    public const DECLINED = 'DECLINED';            // Admin tolak
    public const CANCELLED = 'CANCELLED';

    public const LABELS = [
        self::SUBMITTED => ['Dihantar — menunggu penilaian', 'warn'],
        self::IN_SCOPE => ['Revision dalam skop (tiada caj)', 'ok'],
        self::QUOTED => ['Sebut harga kerja tambahan — menunggu kelulusan anda', 'warn'],
        self::APPROVED => ['Diluluskan', 'ok'],
        self::REJECTED_BY_CLIENT => ['Ditolak oleh anda', ''],
        self::DECLINED => ['Tidak dapat diteruskan', 'bad'],
        self::CANCELLED => ['Dibatalkan', ''],
    ];

    public const OPEN = [self::SUBMITTED, self::QUOTED];

    protected $fillable = [
        'number', 'project_id', 'is_sandbox', 'quotation_id', 'customer_user_id', 'title', 'description', 'status', 'assessment', 'admin_note',
        'amount', 'extra_weeks', 'invoice_id', 'assessed_by_admin_id', 'assessed_at', 'decided_at', 'decision_metadata',
    ];

    protected function casts(): array
    {
        return ['is_sandbox' => 'boolean', 'amount' => 'decimal:2', 'extra_weeks' => 'integer', 'assessed_at' => 'datetime', 'decided_at' => 'datetime', 'decision_metadata' => 'array'];
    }

    public function label(): string { return self::LABELS[$this->status][0] ?? $this->status; }
    public function tone(): string { return self::LABELS[$this->status][1] ?? ''; }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_user_id'); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}
