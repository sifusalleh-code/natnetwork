<?php

namespace App\Engines\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuilderFile extends Model
{
    public const KINDS = ['logo' => 'Logo', 'reference' => 'Rujukan'];

    protected $fillable = ['builder_session_id', 'kind', 'original_name', 'path', 'mime', 'size'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BuilderSession::class, 'builder_session_id');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }
}
