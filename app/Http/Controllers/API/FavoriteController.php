<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteClinicResource;
use App\Http\Resources\FavoriteDoctorResource;
use App\Models\Clinic;
use App\Models\Favorite;
use App\Models\Patient;
use App\Services\FavoriteService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
    ) {
    }

    public function index(Request $request)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        if (! $patient) {
            return ApiResponse::error('Only patients can view favorites.', 403);
        }

        $lists = $this->favoriteService->listForPatient($patient, $request);

        $doctorsPaginator = $lists['doctors'];
        $clinicsPaginator = $lists['clinics'];

        return ApiResponse::success([
            'doctors' => [
                'data' => FavoriteDoctorResource::collection($doctorsPaginator),
                'meta' => [
                    'current_page' => $doctorsPaginator->currentPage(),
                    'last_page' => $doctorsPaginator->lastPage(),
                    'per_page' => $doctorsPaginator->perPage(),
                    'total' => $doctorsPaginator->total(),
                ],
            ],
            'clinics' => [
                'data' => FavoriteClinicResource::collection($clinicsPaginator),
                'meta' => [
                    'current_page' => $clinicsPaginator->currentPage(),
                    'last_page' => $clinicsPaginator->lastPage(),
                    'per_page' => $clinicsPaginator->perPage(),
                    'total' => $clinicsPaginator->total(),
                ],
            ],
        ]);
    }

    public function favoriteClinic(Request $request, Clinic $clinic)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        if (! $patient) {
            return ApiResponse::error('Only patients can favorite clinics.', 403);
        }

        $this->favoriteService->favoriteClinic($clinic, $patient);

        return ApiResponse::success(['is_favorite' => true]);
    }

    public function unfavoriteClinic(Request $request, Clinic $clinic)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        if (! $patient) {
            return ApiResponse::error('Only patients can unfavorite clinics.', 403);
        }

        $this->favoriteService->unfavoriteClinic($clinic, $patient);

        return ApiResponse::success(['is_favorite' => false]);
    }

    public function destroy(Request $request, Favorite $favorite)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        if (! $patient) {
            return ApiResponse::error('Only patients can remove favorites.', 403);
        }

        if ($favorite->patient_id !== $patient->id) {
            return ApiResponse::error('Not found.', 404);
        }

        $this->favoriteService->deleteFavorite($favorite);

        return ApiResponse::success(null, 'Favorite removed.');
    }
}
