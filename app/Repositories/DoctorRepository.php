<?php

namespace App\Repositories;

use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DoctorRepository
{
    public function findDetails(int $doctorId, ?int $patientId): Doctor
    {
        $query = Doctor::query()
            ->with([
                'user',
                'specialization',
                'clinic',
                'reviews.patient.user',
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereKey($doctorId);

        if ($patientId) {
            $query->withExists([
                'favorites as is_favorite' => function (Builder $q) use ($patientId): void {
                    $q->where('favorites.patient_id', $patientId);
                },
            ]);
        }

        /** @var Doctor|null $doctor */
        $doctor = $query->first();

        if (! $doctor) {
            throw new ModelNotFoundException("Doctor with id {$doctorId} not found.");
        }

        return $doctor;
    }

    public function getNearby(?float $lat, ?float $lng, ?int $patientId, int $perPage = 10): LengthAwarePaginator
    {
        $query = Doctor::query()
            ->with(['specialization', 'clinic'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($patientId) {
            $query->withExists([
                'favorites as is_favorite' => function (Builder $q) use ($patientId): void {
                    $q->where('favorites.patient_id', $patientId);
                },
            ]);
        }

        if ($lat !== null && $lng !== null) {
            $haversine = sprintf(
                '(6371 * acos(cos(radians(%F)) * cos(radians(clinics.latitude)) * cos(radians(clinics.longitude) - radians(%F)) + sin(radians(%F)) * sin(radians(clinics.latitude))))',
                $lat,
                $lng,
                $lat
            );

            $query
                ->join('clinics', 'doctors.clinic_id', '=', 'clinics.id')
                ->select('doctors.*')
                ->selectRaw("{$haversine} as distance_km")
                ->whereRaw("{$haversine} <= ?", [20])
                ->orderBy('distance_km');
        } else {
            $query
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count');
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage);

        return $paginator;
    }

    public function addFavorite(int $doctorId, int $patientId): void
    {
        \App\Models\Favorite::firstOrCreate([
            'patient_id' => $patientId,
            'favoritable_type' => Doctor::class,
            'favoritable_id' => $doctorId,
        ]);
    }

    public function removeFavorite(int $doctorId, int $patientId): void
    {
        \App\Models\Favorite::where('patient_id', $patientId)
            ->where('favoritable_type', Doctor::class)
            ->where('favoritable_id', $doctorId)
            ->delete();
    }
}

