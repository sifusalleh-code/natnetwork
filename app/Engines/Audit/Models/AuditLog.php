<?php

namespace App\Engines\Audit\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_type', 'actor_id', 'action', 'entity_type', 'entity_id', 'previous_state', 'new_state', 'reason', 'ip_hash', 'user_agent', 'created_at'];

    protected function casts(): array
    {
        return ['previous_state' => 'array', 'new_state' => 'array', 'created_at' => 'datetime'];
    }

    // Critical audit history immutable (AGENTS.md §13).
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \LogicException('Audit log tidak boleh diubah.');
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new \LogicException('Audit log tidak boleh dipadam.');
    }
}
