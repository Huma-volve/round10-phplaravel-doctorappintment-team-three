<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', 422, $validator->errors());
        }

        $idToken = $validator->validated()['id_token'];

        $googleUser = Socialite::driver('google')->userFromToken($idToken);

        $user = \App\Models\User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Google User',
                'password' => '!', // unusable, login via Google only unless password set later
                'role' => 'patient',
            ]
        );

        $token = $user->createToken('api')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'role' => $user->role,
            'token' => $token,
        ], 'Logged in with Google');
    }
}

