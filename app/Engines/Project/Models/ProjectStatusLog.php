<?php

namespace App\Engines\Project\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatusLog extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['project_id', 'from_status', 'to_status', 'reason', 'admin_id', 'created_at'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
}
