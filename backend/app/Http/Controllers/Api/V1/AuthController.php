<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends BaseApiController
{
    /**
     * Authenticate user with email and password
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->error('The provided credentials do not match our records.', 422, [
                'email' => ['Invalid email or password.'],
            ]);
        }

        if (!$user->is_active) {
            return $this->error('Your account is currently disabled. Please contact an administrator.', 403);
        }

        $user->update(['last_login_at' => now()]);

        $deviceName = $validated['device_name'] ?? 'web_session';
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Authenticated successfully.');
    }

    /**
     * Get the authenticated user profile
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    /**
     * Log the user out (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Generate Google OAuth redirect URL
     */
    public function googleRedirect(): JsonResponse
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return $this->success(['url' => $url]);
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback(Request $request): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?? 'User',
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'password' => Hash::make(bin2hex(random_bytes(16))),
                    'is_active' => true,
                    'last_login_at' => now(),
                ]);
            } else {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar() ?: $user->avatar_url,
                    'last_login_at' => now(),
                ]);
            }

            $token = $user->createToken('google_oauth')->plainTextToken;

            return $this->success([
                'token' => $token,
                'user' => new UserResource($user),
            ], 'Authenticated via Google successfully.');
        } catch (\Throwable $e) {
            return $this->error('Failed to authenticate with Google: ' . $e->getMessage(), 400);
        }
    }
}
