<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->getKey(),
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo' => $this->photo,
            'is_active' => (bool) ($this->is_active ?? true),
            'profile_ids' => collect($this->profile_ids ?? [])
                ->map(fn (mixed $profileId): string => (string) $profileId)
                ->values()
                ->all(),
            'profiles' => ProfileResource::collection($this->whenLoaded('profiles')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
