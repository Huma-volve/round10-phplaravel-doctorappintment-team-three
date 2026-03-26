<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\ReviewAndFeedbackService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewAndFeedbackService $reviewAndFeedbackService,
    ) {
    }

    public function indexForDoctor(Request $request, Doctor $doctor)
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $paginator = $this->reviewAndFeedbackService->paginateDoctorReviews($doctor, $perPage);

        return ApiResponse::success([
            'data' => ReviewResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function storeReview(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $review = $this->reviewAndFeedbackService->submitReview(
            $request->user(),
            $appointment,
            (int) $validated['rating'],
            $validated['comment'] ?? null,
        );

        return ApiResponse::success(new ReviewResource($review->load('patient.user')), null, 201);
    }

    public function storeSessionFeedback(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $feedback = $this->reviewAndFeedbackService->submitSessionFeedback(
            $request->user(),
            $appointment,
            (int) $validated['rating'],
            $validated['comment'] ?? null,
            $validated['tags'] ?? null,
        );

        return ApiResponse::success([
            'id' => $feedback->id,
            'appointment_id' => $feedback->appointment_id,
            'rating' => $feedback->rating,
            'comment' => $feedback->comment,
            'tags' => $feedback->tags,
            'created_at' => $feedback->created_at,
        ], null, 201);
    }
}
