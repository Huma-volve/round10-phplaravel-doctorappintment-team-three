<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorDetailsResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'specialization' => $this->specialization?->name,
            'clinic' => [
                'id' => $this->clinic?->id,
                'name' => $this->clinic?->name_clinic,
                'phone' => $this->clinic?->phone,
                'address' => $this->clinic?->address,
                'latitude' => $this->clinic?->latitude,
                'longitude' => $this->clinic?->longitude,
            ],
            'clinic_address' => $this->clinic_address,
            'license_number' => $this->license_number,
            'bio' => $this->bio,
            'session_price' => $this->session_price,
            'rating' => $this->reviews_avg_rating ? round((float) $this->reviews_avg_rating, 1) : null,
            'reviews_count' => $this->reviews_count ?? 0,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}

