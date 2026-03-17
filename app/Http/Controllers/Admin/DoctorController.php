<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(): View
    {
        $doctors = Doctor::with(['user', 'clinic', 'specialization'])->paginate(15);

        return view('admin.doctors.index', compact('doctors'));
    }

    public function create(): View
    {
        $clinics = Clinic::all();
        $specializations = Specialization::all();

        return view('admin.doctors.create', compact('clinics', 'specializations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'clinic_id' => ['required', 'exists:clinics,id'],
            'specialization_id' => ['required', 'exists:specializations,id'],
            'clinic_address' => ['required', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'session_price' => ['required', 'numeric', 'min:0'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'doctor',
        ]);

        Doctor::create([
            'user_id' => $user->id,
            'clinic_id' => $validated['clinic_id'],
            'specialization_id' => $validated['specialization_id'],
            'clinic_address' => $validated['clinic_address'],
            'license_number' => $validated['license_number'],
            'bio' => $validated['bio'] ?? '',
            'session_price' => $validated['session_price'],
        ]);

        return redirect()->route('admin.doctors.index')->with('status', 'Doctor created successfully.');
    }
}

