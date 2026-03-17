<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $user = $this->authService->registerPatient(
            $validator->validated()['name'],
            $validator->validated()['email'],
            $validator->validated()['password'],
            $validator->validated()['mobile_number'],
            $validator->validated()['lat'] ?? null,
            $validator->validated()['lng'] ?? null,
        );

        return ApiResponse::success([
            'user' => $user,
            'role' => $user->role,
        ], 'Registered successfully');
    }

    public function login(LoginRequest $request)
    {
        $data = $this->authService->login(
            $request->input('email'),
            $request->input('password'),
        );

        if (! $data) {
            return ApiResponse::error('Invalid credentials', 422);
        }

        return ApiResponse::success([
            'user' => $data['user'],
            'role' => $data['user']->role,
            'token' => $data['token'],
        ], 'Logged in successfully');
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        $token = $request->bearerToken();

        if ($token) {
            $this->authService->logout($user, $token);
        }

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        $this->authService->deleteAccount($user);

        return ApiResponse::success(null, 'Account deleted successfully');
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $this->authService->requestPasswordReset($validator->validated()['email']);

        return ApiResponse::success(null, 'OTP sent to email');
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'context' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $data = $validator->validated();

        $ok = $this->authService->verifyOtp(
            $data['email'],
            $data['otp'],
            $data['context'] ?? 'password_reset',
        );

        if (! $ok) {
            return ApiResponse::error('Invalid or expired OTP', 422);
        }

        return ApiResponse::success(null, 'OTP verified');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $data = $validator->validated();

        $ok = $this->authService->resetPassword(
            $data['email'],
            $data['otp'],
            $data['password'],
        );

        if (! $ok) {
            return ApiResponse::error('Invalid or expired OTP', 422);
        }

        return ApiResponse::success(null, 'Password reset successfully');
    }
}

