<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('signin');
        }

        if (empty($roles)) {
            return $next($request);
        }

        $hasRole = $user->userRoles()
            ->whereHas('role', fn ($query) => $query->whereIn('code', $roles))
            ->exists();

        if (! $hasRole) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden.'], 403)
                : abort(403);
        }

        return $next($request);
    }
}
