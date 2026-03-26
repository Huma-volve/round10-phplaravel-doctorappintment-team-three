<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\Favorite;
use App\Models\Patient;
use App\Repositories\FavoriteRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class FavoriteService
{
    public function __construct(
        private readonly FavoriteRepository $favoriteRepository,
    ) {
    }

    /**
     * @return array{doctors: LengthAwarePaginator, clinics: LengthAwarePaginator}
     */
    public function listForPatient(Patient $patient, Request $request): array
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        return [
            'doctors' => $this->favoriteRepository->paginateFavoriteDoctors($patient->id, $perPage),
            'clinics' => $this->favoriteRepository->paginateFavoriteClinics($patient->id, $perPage),
        ];
    }

    public function favoriteClinic(Clinic $clinic, Patient $patient): void
    {
        $this->favoriteRepository->addClinicFavorite($clinic->id, $patient->id);
    }

    public function unfavoriteClinic(Clinic $clinic, Patient $patient): void
    {
        $this->favoriteRepository->removeClinicFavorite($clinic->id, $patient->id);
    }

    public function deleteFavorite(Favorite $favorite): void
    {
        $favorite->delete();
    }
}
