<?php

namespace App\Repositories;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Favorite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FavoriteRepository
{
    public function paginateFavoriteDoctors(int $patientId, int $perPage = 15): LengthAwarePaginator
    {
        return Doctor::query()
            ->join('favorites', function ($join) use ($patientId): void {
                $join->on('doctors.id', '=', 'favorites.favoritable_id')
                    ->where('favorites.favoritable_type', '=', Doctor::class)
                    ->where('favorites.patient_id', '=', $patientId);
            })
            ->select('doctors.*')
            ->orderByDesc('favorites.created_at')
            ->with(['user', 'specialization', 'clinic'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->withExists([
                'favorites as is_favorite' => function ($q) use ($patientId): void {
                    $q->where('favorites.patient_id', $patientId);
                },
            ])
            ->paginate($perPage);
    }

    public function paginateFavoriteClinics(int $patientId, int $perPage = 15): LengthAwarePaginator
    {
        return Clinic::query()
            ->join('favorites', function ($join) use ($patientId): void {
                $join->on('clinics.id', '=', 'favorites.favoritable_id')
                    ->where('favorites.favoritable_type', '=', Clinic::class)
                    ->where('favorites.patient_id', '=', $patientId);
            })
            ->select('clinics.*')
            ->orderByDesc('favorites.created_at')
            ->withCount('doctors')
            ->paginate($perPage);
    }

    public function addClinicFavorite(int $clinicId, int $patientId): void
    {
        Favorite::firstOrCreate([
            'patient_id' => $patientId,
            'favoritable_type' => Clinic::class,
            'favoritable_id' => $clinicId,
        ]);
    }

    public function removeClinicFavorite(int $clinicId, int $patientId): void
    {
        Favorite::where('patient_id', $patientId)
            ->where('favoritable_type', Clinic::class)
            ->where('favoritable_id', $clinicId)
            ->delete();
    }
}
