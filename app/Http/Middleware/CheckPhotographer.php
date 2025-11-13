<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPhotographer
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

        if (!$user->isPhotographer()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Cette ressource est réservée aux photographes.',
            ], 403);
        }

        // Check if photographer profile is approved
        if ($user->photographerProfile && !$user->photographerProfile->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre profil photographe n\'a pas encore été approuvé par un administrateur.',
                'photographer_status' => $user->photographerProfile->status,
            ], 403);
        }

        return $next($request);
    }
}
