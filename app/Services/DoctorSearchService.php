<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\SearchHistory;
use App\Repositories\DoctorRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DoctorSearchService
{
    public function __construct(
        private readonly DoctorRepository $doctorRepository,
    ) {
    }

    public function search(?string $keyword, ?int $specializationId, ?float $lat, ?float $lng, ?Patient $patient): LengthAwarePaginator
    {
        $paginator = $this->doctorRepository->getNearby($lat, $lng, $patient?->id);

        if ($keyword || $specializationId) {
            $query = $paginator->getCollection();

            $filtered = $query->filter(function ($doctor) use ($keyword, $specializationId) {
                $match = true;

                if ($keyword) {
                    $nameMatch = str_contains(strtolower($doctor->user?->name ?? ''), strtolower($keyword));
                    $specialtyMatch = str_contains(strtolower($doctor->specialization?->name ?? ''), strtolower($keyword));
                    $match = $match && ($nameMatch || $specialtyMatch);
                }

                if ($specializationId) {
                    $match = $match && $doctor->specialization_id === $specializationId;
                }

                return $match;
            });

            $paginator->setCollection($filtered->values());
        }

        if ($patient && $keyword) {
            SearchHistory::create([
                'patient_id' => $patient->id,
                'keyword' => $keyword,
            ]);
        }

        return $paginator;
    }
}

