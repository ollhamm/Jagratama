<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $this->authService->login($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login berhasil.',
                'user' => $request->user(),
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $this->authService->logout();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logout berhasil.',
            ]);
        }

        return redirect()->route('signin');
    }
}
