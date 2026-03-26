<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        /** @var User|null $viewer */
        $viewer = $this->additional['viewer'] ?? $request->user();

        $otherName = null;
        if ($viewer && $this->relationLoaded('patient') && $this->relationLoaded('doctor')) {
            if ($this->patient?->user_id === $viewer->id) {
                $otherName = $this->doctor?->user?->name;
            } elseif ($this->doctor?->user_id === $viewer->id) {
                $otherName = $this->patient?->user?->name;
            }
        }

        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'other_party_name' => $otherName,
            'is_favorite' => (bool) ($this->resource->getAttribute('pivot_is_favorite') ?? false),
            'is_archived' => (bool) ($this->resource->getAttribute('pivot_is_archived') ?? false),
            'last_read_at' => $this->resource->getAttribute('pivot_last_read_at'),
            'unread_count' => $this->when(
                $this->resource->getAttribute('unread_count') !== null,
                (int) $this->resource->getAttribute('unread_count')
            ),
            'updated_at' => $this->updated_at,
        ];
    }
}
