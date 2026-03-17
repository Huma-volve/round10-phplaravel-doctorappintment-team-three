<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Patient;
use App\Repositories\DoctorRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DoctorService
{
    public function __construct(
        private readonly DoctorRepository $doctorRepository,
    ) {
    }

    public function getDoctorDetails(int $doctorId, ?Patient $patient): Doctor
    {
        return $this->doctorRepository->findDetails($doctorId, $patient?->id);
    }

    public function getNearbyDoctors(?float $lat, ?float $lng, ?Patient $patient): LengthAwarePaginator
    {
        return $this->doctorRepository->getNearby($lat, $lng, $patient?->id);
    }

    public function favoriteDoctor(Doctor $doctor, Patient $patient): void
    {
        $this->doctorRepository->addFavorite($doctor->id, $patient->id);
    }

    public function unfavoriteDoctor(Doctor $doctor, Patient $patient): void
    {
        $this->doctorRepository->removeFavorite($doctor->id, $patient->id);
    }
}

