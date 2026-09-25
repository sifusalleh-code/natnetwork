<?php

namespace App\Engines\ProjectContent\Models;

use App\Engines\Project\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectContentItem extends Model
{
    public const REQUESTED = 'REQUESTED';
    public const SUBMITTED = 'SUBMITTED';
    public const ACCEPTED = 'ACCEPTED';
    public const NEEDS_UPDATE = 'NEEDS_UPDATE';

    public const LABELS = [self::REQUESTED => ['Diminta', 'warn'], self::SUBMITTED => ['Dihantar — menunggu semakan', ''], self::ACCEPTED => ['Diterima', 'ok'], self::NEEDS_UPDATE => ['Perlu kemas kini', 'bad']];

    protected $fillable = ['project_id', 'title', 'description', 'kind', 'status', 'blocking', 'info_text', 'admin_note', 'help_requested', 'submitted_at', 'reviewed_at'];
    protected function casts(): array { return ['blocking' => 'boolean', 'help_requested' => 'boolean', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime']; }

    public function needsClientAction(): bool { return in_array($this->status, [self::REQUESTED, self::NEEDS_UPDATE], true); }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function files(): HasMany { return $this->hasMany(ProjectFile::class, 'content_item_id')->latest('version'); }
}
