<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorSummaryResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'specialization' => $this->specialization?->name,
            'clinic_name' => $this->clinic?->name_clinic,
            'session_price' => $this->session_price,
            'rating' => $this->reviews_avg_rating ? round((float) $this->reviews_avg_rating, 1) : null,
            'reviews_count' => $this->reviews_count ?? 0,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'distance_km' => $this->when(isset($this->distance_km), function () {
                return round((float) $this->distance_km, 2);
            }),
        ];
    }
}

