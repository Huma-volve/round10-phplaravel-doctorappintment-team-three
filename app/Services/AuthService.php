<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\Patient;
use App\Models\User;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    public function registerPatient(string $name, string $email, string $password, string $mobileNumber, ?float $lat, ?float $lng): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'patient',
            'mobile_number' => $mobileNumber,
            'latitude' => $lat ?? 0,
            'longitude' => $lng ?? 0,
        ]);

        Patient::create([
            'user_id' => $user->id,
        ]);

        return $user;
    }

    public function login(string $email, string $password): ?array
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken('api')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user, string $token): void
    {
        $user->tokens()->where('id', explode('|', $token)[0] ?? null)->delete();
    }

    public function deleteAccount(User $user): void
    {
        if ($user->patient) {
            $user->patient->delete();
        }

        if ($user->doctor) {
            $user->doctor->delete();
        }

        $user->delete();
    }

    public function requestPasswordReset(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();

        $code = (string) random_int(100000, 999999);

        Otp::updateOrCreate(
            ['email' => $user->email, 'context' => 'password_reset'],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => CarbonImmutable::now()->addMinutes(15),
                'attempts' => 0,
                'verified_at' => null,
            ]
        );

        Mail::raw("Your password reset code is: {$code}", function ($message) use ($user): void {
            $message->to($user->email)->subject('Password Reset Code');
        });
    }

    public function verifyOtp(string $email, string $code, string $context = 'password_reset'): bool
    {
        /** @var Otp|null $otp */
        $otp = Otp::where('email', $email)
            ->where('context', $context)
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->expires_at->isPast() || $otp->attempts >= 5) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->forceFill([
            'verified_at' => CarbonImmutable::now(),
        ])->save();

        return true;
    }

    public function resetPassword(string $email, string $code, string $password): bool
    {
        /** @var Otp|null $otp */
        $otp = Otp::where('email', $email)
            ->where('context', 'password_reset')
            ->first();

        if (! $otp || ! $otp->verified_at || $otp->expires_at->isPast()) {
            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        $otp->delete();

        return true;
    }
}

