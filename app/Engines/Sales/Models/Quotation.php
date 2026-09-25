<?php

namespace App\Engines\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_REVIEW_REQUIRED = 'REVIEW REQUIRED';
    public const STATUS_SENT = 'SENT';
    public const STATUS_VIEWED = 'VIEWED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_DECLINED = 'DECLINED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = ['project_request_id', 'master_specification_id', 'number', 'status', 'price_snapshot', 'terms_snapshot', 'total_amount', 'estimated_weeks', 'valid_until', 'sent_at', 'viewed_at', 'accepted_at', 'accepted_by_user_id', 'acceptance_metadata', 'payment_completed_at', 'payment_completed_by_admin_id', 'invalidated_at'];
    protected function casts(): array { return ['price_snapshot' => 'array', 'terms_snapshot' => 'array', 'total_amount' => 'decimal:2', 'valid_until' => 'datetime', 'sent_at' => 'datetime', 'viewed_at' => 'datetime', 'accepted_at' => 'datetime', 'acceptance_metadata' => 'array', 'payment_completed_at' => 'datetime', 'invalidated_at' => 'datetime']; }
    public function isAwaitingCustomer(): bool { return in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED], true) && ! $this->isExpired(); }
    public function isExpired(): bool { return $this->status === self::STATUS_EXPIRED || (in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED], true) && $this->valid_until?->isPast()); }
    public function items(): array { return $this->price_snapshot['items'] ?? []; }
    public function request(): BelongsTo { return $this->belongsTo(ProjectRequest::class, 'project_request_id'); }
    public function specification(): BelongsTo { return $this->belongsTo(MasterSpecification::class, 'master_specification_id'); }
}
