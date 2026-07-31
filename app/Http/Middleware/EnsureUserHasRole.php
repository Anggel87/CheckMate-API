<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->role->name, $roles, true)) {
            throw ApiException::forbidden('No tienes permiso para acceder a este portal.', 'AUTH02');
        }

        return $next($request);
    }
}
