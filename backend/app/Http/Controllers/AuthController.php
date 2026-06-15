<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Logging\AppLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AppLogger $logger)
    {
    }

    /**
     * @throws ValidationException
     */
    public function login(LoginRequest $request): UserResource
    {
        $credentials = $request->validated();

        $this->logger->log('AuthController@login.start', [
            'email' => $credentials['email'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            $this->logger->log('AuthController@login.failed', [
                'email' => $credentials['email'],
            ]);

            throw ValidationException::withMessages([
                'email' => ['Неверный логин или пароль.'],
            ]);
        }

        $request->session()->regenerate();

        $this->logger->log('AuthController@login.done', [
            'user_id' => $request->user()?->id,
        ]);

        return UserResource::make($request->user());
    }

    public function me(Request $request): UserResource
    {
        $this->logger->log('AuthController@me', [
            'user_id' => $request->user()?->id,
        ]);

        return UserResource::make($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logger->log('AuthController@logout.start', [
            'user_id' => $request->user()?->id,
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->logger->log('AuthController@logout.done');

        return response()->json(['message' => 'Logged out']);
    }
}
