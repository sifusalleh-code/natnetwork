<?php

namespace App\Engines\ProjectContent\Models;

use App\Engines\Project\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    public const CLIENT = 'CLIENT';
    public const INTERNAL = 'INTERNAL';

    protected $fillable = ['project_id', 'content_item_id', 'uploaded_by', 'visibility', 'is_deliverable', 'version', 'path', 'original_name', 'mime', 'size'];
    protected function casts(): array { return ['is_deliverable' => 'boolean', 'size' => 'integer', 'version' => 'integer']; }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function item(): BelongsTo { return $this->belongsTo(ProjectContentItem::class, 'content_item_id'); }
}
