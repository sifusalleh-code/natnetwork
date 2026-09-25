<?php

namespace App\Engines\Communication\Models;

use App\Engines\Project\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const OPEN = 'OPEN';
    public const ANSWERED = 'ANSWERED';
    public const CLOSED = 'CLOSED';
    public const LABELS = [self::OPEN => ['Dibuka — menunggu balasan', 'warn'], self::ANSWERED => ['Dibalas', 'ok'], self::CLOSED => ['Ditutup', '']];

    protected $fillable = ['number', 'customer_user_id', 'project_id', 'subject', 'status', 'last_activity_at'];
    protected function casts(): array { return ['last_activity_at' => 'datetime']; }

    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_user_id'); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function messages(): HasMany { return $this->hasMany(SupportMessage::class)->orderBy('id'); }
}
