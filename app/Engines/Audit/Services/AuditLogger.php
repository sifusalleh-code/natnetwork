<?php

namespace App\Engines\Audit\Services;

use App\Engines\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogger
{
    public function record(string $action, ?Model $actor, ?Model $entity = null, ?array $previous = null, ?array $new = null, ?string $reason = null): AuditLog
    {
        $request = request();

        return AuditLog::query()->create([
            'actor_type' => $actor ? class_basename($actor) : 'SYSTEM',
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'previous_state' => $previous,
            'new_state' => $new,
            'reason' => $reason,
            'ip_hash' => $request?->ip() ? hash('sha256', $request->ip().'|'.config('app.key')) : null,
            'user_agent' => $request ? (Str::limit((string) $request->userAgent(), 490, '') ?: null) : null,
            'created_at' => now(),
        ]);
    }
}
