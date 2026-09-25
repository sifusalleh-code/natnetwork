<?php

namespace App\Engines\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuilderAnswer extends Model
{
    protected $fillable = ['builder_session_id', 'builder_question_id', 'value', 'text_value'];
    protected function casts(): array { return ['value' => 'array']; }
    public function session(): BelongsTo { return $this->belongsTo(BuilderSession::class, 'builder_session_id'); }
    public function question(): BelongsTo { return $this->belongsTo(BuilderQuestion::class, 'builder_question_id'); }
}
