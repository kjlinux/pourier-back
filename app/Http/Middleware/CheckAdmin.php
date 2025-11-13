<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Cette ressource est réservée aux administrateurs.',
            ], 403);
        }

        return $next($request);
    }
}
