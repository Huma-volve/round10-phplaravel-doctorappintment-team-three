<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Favorite;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Review;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorTestSeeder extends Seeder
{
    public function run(): void
    {
        $specialization = Specialization::firstOrCreate(['name' => 'General Practitioner']);

        $clinicCenterLat = 37.7749;
        $clinicCenterLng = -122.4194;

        $clinic = Clinic::firstOrCreate([
            'name_clinic' => 'Central Health Clinic',
        ], [
            'phone' => '+1-555-1000',
            'address' => '123 Main St',
            'latitude' => $clinicCenterLat,
            'longitude' => $clinicCenterLng,
        ]);

        $clinic2 = Clinic::firstOrCreate([
            'name_clinic' => 'Westside Medical',
        ], [
            'phone' => '+1-555-2000',
            'address' => '456 West St',
            'latitude' => $clinicCenterLat + 0.02,
            'longitude' => $clinicCenterLng + 0.02,
        ]);

        $patientUser = User::firstOrCreate(
            ['email' => 'patient@example.com'],
            [
                'name' => 'Test Patient',
                'mobile_number' => '0000000000',
                'password' => Hash::make('password'),
                'latitude' => $clinicCenterLat,
                'longitude' => $clinicCenterLng,
            ]
        );

        $patient = Patient::firstOrCreate([
            'user_id' => $patientUser->id,
        ]);

        $doctors = [];
        foreach ([
            ['name' => 'Dr. Alice Smith', 'clinic' => $clinic, 'rating' => 4],
            ['name' => 'Dr. Bob Johnson', 'clinic' => $clinic2, 'rating' => 5],
        ] as $index => $config) {
            $user = User::firstOrCreate(
                ['email' => 'doctor'.$index.'@example.com'],
                [
                    'name' => $config['name'],
                    'mobile_number' => '000000000'.($index + 1),
                    'password' => Hash::make('password'),
                    'latitude' => $config['clinic']->latitude,
                    'longitude' => $config['clinic']->longitude,
                ]
            );

            $doctor = Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'clinic_id' => $config['clinic']->id,
                    'specialization_id' => $specialization->id,
                    'clinic_address' => $config['clinic']->address,
                    'license_number' => 'LIC-'.($index + 1),
                    'bio' => 'Experienced doctor for testing purposes.',
                    'session_price' => 100 + ($index * 20),
                ]
            );

            $doctors[] = $doctor;

            $appointment = Appointment::firstOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'appointment_time' => now(),
                ],
                [
                    'Status' => 'completed',
                ]
            );

            Review::firstOrCreate([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_id' => $appointment->id,
            ], [
                'rating' => $config['rating'],
                'comment' => 'Great doctor!',
            ]);
        }

        if (isset($doctors[0])) {
            Favorite::firstOrCreate([
                'patient_id' => $patient->id,
                'doctor_id' => $doctors[0]->id,
            ]);
        }
    }
}

