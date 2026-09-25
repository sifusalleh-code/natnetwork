<?php

namespace App\Engines\Project\Models;

use App\Engines\Billing\Models\Invoice;
use App\Engines\ProjectContent\Models\ProjectContentItem;
use App\Engines\ProjectContent\Models\ProjectFile;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const WAITING_TO_START = 'WAITING_TO_START';
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const WAITING_FOR_CLIENT = 'WAITING_FOR_CLIENT';
    public const WAITING_FOR_PAYMENT = 'WAITING_FOR_PAYMENT';
    public const READY_TO_RESUME = 'READY_TO_RESUME';
    public const CLIENT_REVIEW = 'CLIENT_REVIEW';
    public const IN_REVISION = 'IN_REVISION';
    public const FINAL_APPROVAL = 'FINAL_APPROVAL';
    public const READY_FOR_HANDOVER = 'READY_FOR_HANDOVER';
    public const COMPLETED = 'COMPLETED';
    public const ON_HOLD = 'ON_HOLD';
    public const CANCELLED = 'CANCELLED';

    /** Peralihan status yang sah (master spec §10). START PROJECT dan COMPLETED guna command khusus. */
    public const TRANSITIONS = [
        self::WAITING_TO_START => [self::ON_HOLD, self::CANCELLED],
        self::IN_PROGRESS => [self::WAITING_FOR_CLIENT, self::WAITING_FOR_PAYMENT, self::CLIENT_REVIEW, self::ON_HOLD, self::CANCELLED],
        self::WAITING_FOR_CLIENT => [self::READY_TO_RESUME, self::ON_HOLD, self::CANCELLED],
        self::READY_TO_RESUME => [self::IN_PROGRESS],
        self::WAITING_FOR_PAYMENT => [self::IN_PROGRESS, self::CLIENT_REVIEW],
        self::CLIENT_REVIEW => [self::IN_REVISION, self::FINAL_APPROVAL, self::WAITING_FOR_PAYMENT],
        self::IN_REVISION => [self::CLIENT_REVIEW],
        self::FINAL_APPROVAL => [self::READY_FOR_HANDOVER, self::IN_REVISION],
        self::READY_FOR_HANDOVER => [],
        self::ON_HOLD => [self::IN_PROGRESS, self::CANCELLED],
        self::COMPLETED => [],
        self::CANCELLED => [],
    ];

    public const REASON_REQUIRED = [self::WAITING_FOR_CLIENT, self::ON_HOLD, self::CANCELLED];

    public const CLIENT_LABELS = [
        self::WAITING_TO_START => ['Menunggu mula', 'Pesanan disahkan. Projek akan dimulakan mengikut slot yang dipilih.'],
        self::IN_PROGRESS => ['Dalam pembangunan', 'Pasukan kami sedang membangunkan projek anda.'],
        self::WAITING_FOR_CLIENT => ['Menunggu tindakan anda', 'Projek dihentikan sementara sehingga maklumat/bahan yang diminta diterima. Masa menunggu tidak dikira dalam tempoh pembangunan dan slot boleh dijadualkan semula.'],
        self::WAITING_FOR_PAYMENT => ['Menunggu bayaran', 'Sila jelaskan invois tertunggak untuk meneruskan projek.'],
        self::READY_TO_RESUME => ['Sedia disambung', 'Bahan anda telah diterima. Projek akan disambung mengikut jadual seterusnya.'],
        self::CLIENT_REVIEW => ['Semakan anda', 'Projek sedia untuk semakan anda.'],
        self::IN_REVISION => ['Dalam revision', 'Kami sedang membuat pembetulan berdasarkan maklum balas anda.'],
        self::FINAL_APPROVAL => ['Kelulusan akhir', 'Sila berikan kelulusan akhir.'],
        self::READY_FOR_HANDOVER => ['Sedia diserahkan', 'Projek sedia untuk penyerahan.'],
        self::COMPLETED => ['Selesai', 'Projek telah selesai. Tempoh sokongan percuma bermula.'],
        self::ON_HOLD => ['Ditangguhkan', 'Projek ditangguhkan buat sementara.'],
        self::CANCELLED => ['Dibatalkan', 'Projek telah dibatalkan.'],
    ];

    protected $fillable = [
        'number', 'order_id', 'is_sandbox', 'quotation_id', 'customer_user_id', 'name', 'template', 'status', 'progress', 'planned_start_date',
        'started_at', 'started_by_admin_id', 'final_invoice_id', 'status_note', 'completed_at', 'support_days',
    ];

    protected function casts(): array
    {
        return ['is_sandbox' => 'boolean', 'planned_start_date' => 'date', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'progress' => 'integer', 'support_days' => 'integer'];
    }

    public function isStarted(): bool { return $this->started_at !== null; }
    public function isClosed(): bool { return in_array($this->status, [self::COMPLETED, self::CANCELLED], true); }
    public function clientLabel(): string { return self::CLIENT_LABELS[$this->status][0] ?? $this->status; }
    public function clientExplanation(): string { return self::CLIENT_LABELS[$this->status][1] ?? ''; }
    public function supportEndsAt(): ?\Carbon\CarbonInterface { return $this->completed_at?->copy()->addDays($this->support_days); }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_user_id'); }
    public function finalInvoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'final_invoice_id'); }
    public function milestones(): HasMany { return $this->hasMany(ProjectMilestone::class)->orderBy('position'); }
    public function statusLogs(): HasMany { return $this->hasMany(ProjectStatusLog::class)->latest('id'); }
    public function contentItems(): HasMany { return $this->hasMany(ProjectContentItem::class)->orderBy('id'); }
    public function files(): HasMany { return $this->hasMany(ProjectFile::class)->latest('id'); }
}
