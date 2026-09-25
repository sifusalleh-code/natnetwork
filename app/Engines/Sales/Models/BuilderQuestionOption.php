<?php

namespace App\Engines\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuilderQuestionOption extends Model
{
    protected $fillable = ['builder_question_id', 'code', 'label', 'display_order'];
    public function question(): BelongsTo { return $this->belongsTo(BuilderQuestion::class, 'builder_question_id'); }
}
