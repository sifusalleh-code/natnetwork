<?php

namespace App\Engines\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterSpecification extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_SUPERSEDED = 'SUPERSEDED';

    protected $fillable = ['project_request_id', 'version', 'status', 'requirement_snapshot', 'specification_snapshot', 'technical_mapping', 'approved_by_user_id', 'approved_at', 'superseded_at'];
    protected function casts(): array { return ['requirement_snapshot' => 'array', 'specification_snapshot' => 'array', 'technical_mapping' => 'array', 'approved_at' => 'datetime', 'superseded_at' => 'datetime']; }
    public function request(): BelongsTo { return $this->belongsTo(ProjectRequest::class, 'project_request_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by_user_id'); }
}
