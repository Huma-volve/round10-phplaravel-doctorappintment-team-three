<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DoctorController as AdminDoctorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/doctors', [AdminDoctorController::class, 'index'])->name('admin.doctors.index');
        Route::get('/admin/doctors/create', [AdminDoctorController::class, 'create'])->name('admin.doctors.create');
        Route::post('/admin/doctors', [AdminDoctorController::class, 'store'])->name('admin.doctors.store');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
