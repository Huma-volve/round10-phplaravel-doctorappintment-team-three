<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorSummaryResource;
use App\Models\Patient;
use App\Models\SearchHistory;
use App\Services\DoctorSearchService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class DoctorSearchController extends Controller
{
    public function __construct(
        private readonly DoctorSearchService $doctorSearchService,
    ) {
    }

    public function index(Request $request)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        $paginator = $this->doctorSearchService->search(
            $request->input('q'),
            $request->integer('specialization_id'),
            $request->input('lat') !== null ? (float) $request->input('lat') : null,
            $request->input('lng') !== null ? (float) $request->input('lng') : null,
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

    public function history(Request $request)
    {
        /** @var Patient|null $patient */
        $patient = $request->user()?->patient;

        if (! $patient) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        $history = SearchHistory::where('patient_id', $patient->id)
            ->latest()
            ->get(['id', 'keyword', 'created_at']);

        return ApiResponse::success($history);
    }
}

