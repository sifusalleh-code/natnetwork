<?php

namespace App\Engines\Sales\Models;

use App\Engines\Pricing\Models\ServicePackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRequest extends Model
{
    public const STATUS_DRAFT = 'DRAFT';

    protected $fillable = ['customer_user_id', 'builder_session_id', 'service_package_id', 'status', 'requirement_snapshot'];
    protected function casts(): array { return ['requirement_snapshot' => 'array']; }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_user_id'); }
    public function builderSession(): BelongsTo { return $this->belongsTo(BuilderSession::class); }
    public function servicePackage(): BelongsTo { return $this->belongsTo(ServicePackage::class); }
    public function specifications(): HasMany { return $this->hasMany(MasterSpecification::class); }
}
