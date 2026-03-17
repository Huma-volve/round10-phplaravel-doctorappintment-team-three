<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorDetailsRequest;
use App\Http\Resources\DoctorDetailsResource;
use App\Http\Resources\DoctorSummaryResource;
use App\Models\Doctor;
use App\Models\Patient;
use App\Support\ApiResponse;
use App\Services\DoctorService;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function __construct(
        private readonly DoctorService $doctorService,
    ) {
    }

    public function show(Doctor $doctor, DoctorDetailsRequest $request)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        $doctor = $this->doctorService->getDoctorDetails($doctor->id, $patient);

        return ApiResponse::success(new DoctorDetailsResource($doctor));
    }

    public function nearby(DoctorDetailsRequest $request)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        $lat = $request->input('lat');
        $lng = $request->input('lng');

        $paginator = $this->doctorService->getNearbyDoctors(
            $lat !== null ? (float) $lat : null,
            $lng !== null ? (float) $lng : null,
            $patient
        );

        $resource = DoctorSummaryResource::collection($paginator);

        return ApiResponse::success([
            'data' => $resource,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function favorite(Request $request, Doctor $doctor)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        if (! $patient) {
            return ApiResponse::error('Only patients can favorite doctors.', 403);
        }

        $this->doctorService->favoriteDoctor($doctor, $patient);

        return ApiResponse::success(['is_favorite' => true]);
    }

    public function unfavorite(Request $request, Doctor $doctor)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        if (! $patient) {
            return ApiResponse::error('Only patients can unfavorite doctors.', 403);
        }

        $this->doctorService->unfavoriteDoctor($doctor, $patient);

        return ApiResponse::success(['is_favorite' => false]);
    }
}

