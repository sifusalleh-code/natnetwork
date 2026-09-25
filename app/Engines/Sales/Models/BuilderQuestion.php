<?php

namespace App\Engines\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuilderQuestion extends Model
{
    protected $fillable = ['code', 'label', 'helper', 'question_type', 'service_categories', 'is_required', 'display_order', 'condition', 'internal_mapping', 'is_active'];

    protected function casts(): array
    {
        return ['service_categories' => 'array', 'is_required' => 'boolean', 'condition' => 'array', 'internal_mapping' => 'array', 'is_active' => 'boolean'];
    }

    public function options(): HasMany { return $this->hasMany(BuilderQuestionOption::class)->orderBy('display_order'); }
}
