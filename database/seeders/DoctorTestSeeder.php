<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Chat;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Favorite;
use App\Models\InAppNotification;
use App\Models\Message;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SessionFeedback;
use App\Models\Specialization;
use App\Models\User;
use App\Support\NotificationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DoctorTestSeeder extends Seeder
{
    public function run(): void
    {
        $specializations = [
            'General Practitioner',
            'Cardiologist',
            'Dermatologist',
        ];

        $specializationModels = collect($specializations)
            ->mapWithKeys(fn (string $name) => [$name => Specialization::firstOrCreate(['name' => $name])]);

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

        $clinic3 = Clinic::firstOrCreate([
            'name_clinic' => 'North Valley Hospital',
        ], [
            'phone' => '+1-555-3000',
            'address' => '789 North Ave',
            'latitude' => $clinicCenterLat - 0.015,
            'longitude' => $clinicCenterLng + 0.01,
        ]);

        $patientUser = User::firstOrCreate(
            ['email' => 'patient@example.com'],
            [
                'name' => 'Test Patient',
                'mobile_number' => '0000000000',
                'password' => Hash::make('password'),
                'role' => 'patient',
                'latitude' => $clinicCenterLat,
                'longitude' => $clinicCenterLng,
            ]
        );
        if ($patientUser->role !== 'patient') {
            $patientUser->forceFill(['role' => 'patient'])->save();
        }

        $patientUserTwo = User::firstOrCreate(
            ['email' => 'patient2@example.com'],
            [
                'name' => 'Second Patient',
                'mobile_number' => '0000000099',
                'password' => Hash::make('password'),
                'role' => 'patient',
                'latitude' => $clinicCenterLat + 0.01,
                'longitude' => $clinicCenterLng - 0.01,
            ]
        );
        if ($patientUserTwo->role !== 'patient') {
            $patientUserTwo->forceFill(['role' => 'patient'])->save();
        }

        $patient = Patient::firstOrCreate([
            'user_id' => $patientUser->id,
        ]);

        $patient2 = Patient::firstOrCreate([
            'user_id' => $patientUserTwo->id,
        ]);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Platform Admin',
                'mobile_number' => '0000000010',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'latitude' => $clinicCenterLat,
                'longitude' => $clinicCenterLng,
            ]
        );
        if ($adminUser->role !== 'admin') {
            $adminUser->forceFill(['role' => 'admin'])->save();
        }

        DB::table('admins')->updateOrInsert(
            ['user_id' => $adminUser->id],
            ['permissions' => json_encode(['broadcast_notifications', 'view_system_alerts']), 'updated_at' => now(), 'created_at' => now()]
        );

        $doctors = [];
        foreach ([
            ['name' => 'Dr. Alice Smith', 'clinic' => $clinic, 'rating' => 4, 'specialization' => 'General Practitioner'],
            ['name' => 'Dr. Bob Johnson', 'clinic' => $clinic2, 'rating' => 5, 'specialization' => 'Cardiologist'],
            ['name' => 'Dr. Carol Lee', 'clinic' => $clinic3, 'rating' => 5, 'specialization' => 'Dermatologist'],
        ] as $index => $config) {
            $user = User::firstOrCreate(
                ['email' => 'doctor'.$index.'@example.com'],
                [
                    'name' => $config['name'],
                    'mobile_number' => '000000000'.($index + 1),
                    'password' => Hash::make('password'),
                    'role' => 'doctor',
                    'latitude' => $config['clinic']->latitude,
                    'longitude' => $config['clinic']->longitude,
                ]
            );
            if ($user->role !== 'doctor') {
                $user->forceFill(['role' => 'doctor'])->save();
            }

            $doctor = Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'clinic_id' => $config['clinic']->id,
                    'specialization_id' => $specializationModels[$config['specialization']]->id,
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

        if (isset($doctors[0], $doctors[1], $doctors[2])) {
            Favorite::firstOrCreate([
                'patient_id' => $patient->id,
                'favoritable_type' => Doctor::class,
                'favoritable_id' => $doctors[0]->id,
            ]);

            Favorite::firstOrCreate([
                'patient_id' => $patient->id,
                'favoritable_type' => Clinic::class,
                'favoritable_id' => $clinic2->id,
            ]);

            Favorite::firstOrCreate([
                'patient_id' => $patient2->id,
                'favoritable_type' => Doctor::class,
                'favoritable_id' => $doctors[1]->id,
            ]);

            $confirmedAppointment = Appointment::firstOrCreate(
                [
                    'doctor_id' => $doctors[1]->id,
                    'patient_id' => $patient->id,
                    'appointment_time' => now()->addDay()->startOfHour(),
                ],
                ['Status' => 'confirmed']
            );

            $rescheduledAppointment = Appointment::firstOrCreate(
                [
                    'doctor_id' => $doctors[2]->id,
                    'patient_id' => $patient2->id,
                    'appointment_time' => now()->addDays(2)->startOfHour(),
                ],
                ['Status' => 'rescheduled']
            );

            Payment::firstOrCreate(
                ['appointment_id' => $confirmedAppointment->id],
                [
                    'amount' => 140.0,
                    'payment_method' => 'stripe',
                    'payment_status' => 'paid',
                    'transaction_id' => (string) Str::uuid(),
                ]
            );

            Payment::firstOrCreate(
                ['appointment_id' => $rescheduledAppointment->id],
                [
                    'amount' => 180.0,
                    'payment_method' => 'cash',
                    'payment_status' => 'pending',
                    'transaction_id' => (string) Str::uuid(),
                ]
            );

            SessionFeedback::firstOrCreate(
                ['appointment_id' => $confirmedAppointment->id],
                [
                    'patient_id' => $patient->id,
                    'rating' => 4,
                    'comment' => 'Smooth session experience, short wait time.',
                    'tags' => ['friendly_staff', 'on_time'],
                ]
            );

            $chat = Chat::firstOrCreate(
                [
                    'appointment_id' => $confirmedAppointment->id,
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctors[1]->id,
                ]
            );

            $chat->users()->syncWithoutDetaching([
                $patientUser->id => [
                    'is_favorite' => true,
                    'is_archived' => false,
                    'last_read_at' => now()->subMinutes(10),
                ],
                $doctors[1]->user_id => [
                    'is_favorite' => false,
                    'is_archived' => false,
                    'last_read_at' => now()->subMinutes(1),
                ],
            ]);

            Message::firstOrCreate(
                [
                    'chat_id' => $chat->id,
                    'sender_id' => $patientUser->id,
                    'content' => 'Hello doctor, I need to confirm my appointment details.',
                ],
                [
                    'sender_type' => 'user',
                    'message_type' => 'text',
                    'media_path' => null,
                    'media_mime' => null,
                    'is_read' => false,
                ]
            );

            Message::firstOrCreate(
                [
                    'chat_id' => $chat->id,
                    'sender_id' => $doctors[1]->user_id,
                    'content' => 'Sure, the appointment is confirmed for tomorrow.',
                ],
                [
                    'sender_type' => 'user',
                    'message_type' => 'text',
                    'media_path' => null,
                    'media_mime' => null,
                    'is_read' => true,
                ]
            );

            InAppNotification::firstOrCreate(
                [
                    'user_id' => $patientUser->id,
                    'type' => NotificationType::APPOINTMENT_UPCOMING,
                    'related_id' => $confirmedAppointment->id,
                ],
                [
                    'title' => 'Upcoming appointment',
                    'body' => 'Reminder: your appointment is scheduled for tomorrow.',
                    'is_read' => false,
                    'read_at' => null,
                ]
            );

            InAppNotification::firstOrCreate(
                [
                    'user_id' => $doctors[1]->user_id,
                    'type' => NotificationType::APPOINTMENT_NEW_FOR_DOCTOR,
                    'related_id' => $confirmedAppointment->id,
                ],
                [
                    'title' => 'New booking',
                    'body' => 'A patient has booked a new appointment.',
                    'is_read' => false,
                    'read_at' => null,
                ]
            );

            InAppNotification::firstOrCreate(
                [
                    'user_id' => $doctors[1]->user_id,
                    'type' => NotificationType::REVIEW_NEW_FOR_DOCTOR,
                    'related_id' => $confirmedAppointment->id,
                ],
                [
                    'title' => 'New review',
                    'body' => 'A patient submitted a new review for your session.',
                    'is_read' => false,
                    'read_at' => null,
                ]
            );

            InAppNotification::firstOrCreate(
                [
                    'user_id' => $adminUser->id,
                    'type' => NotificationType::ADMIN_SYSTEM_ALERT,
                    'related_id' => $confirmedAppointment->id,
                ],
                [
                    'title' => 'System alert',
                    'body' => 'A high-value appointment was created and paid.',
                    'is_read' => false,
                    'read_at' => null,
                ]
            );
        }
    }
}

