<?php

namespace App\Engines\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    protected $fillable = ['project_id', 'position', 'name', 'client_label', 'weight', 'completed_at', 'completed_by_admin_id'];
    protected function casts(): array { return ['completed_at' => 'datetime', 'weight' => 'integer']; }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
