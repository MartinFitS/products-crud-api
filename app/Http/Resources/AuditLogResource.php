<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        /** @var User|null $actor */
        $actor = $this->resource->relationLoaded('user')
            ? $this->resource->getRelation('user')
            : null;

        return [
            'id' => (string) $this->getKey(),
            'actor' => $actor ? [
                'id' => (string) $actor->getKey(),
                'code' => $actor->code,
                'name' => $actor->name,
                'email' => $actor->email,
            ] : null,
            'action' => $this->action,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => (string) $this->auditable_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
