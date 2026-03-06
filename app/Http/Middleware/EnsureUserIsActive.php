<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            auth()->logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun Anda tidak aktif.',
                ], 403);
            }

            return redirect()->route('signin')->withErrors([
                'email' => 'Akun Anda tidak aktif.',
            ]);
        }

        return $next($request);
    }
}
