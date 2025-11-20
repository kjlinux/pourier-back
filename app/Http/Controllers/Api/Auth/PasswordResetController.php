<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class PasswordResetController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/forgot-password",
     *     operationId="forgotPassword",
     *     tags={"Authentication"},
     *     summary="Demander la réinitialisation du mot de passe",
     *     description="Envoie un email avec un lien de réinitialisation de mot de passe. Limité à 3 requêtes par 15 minutes par email.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="utilisateur@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email de réinitialisation envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Un email de réinitialisation a été envoyé à votre adresse.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Trop de requêtes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Trop de tentatives. Veuillez réessayer dans quelques minutes.")
     *         )
     *     )
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        // Rate limiting: 3 requests per 15 minutes per email
        $key = 'password-reset:' . $email;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return response()->json([
                'success' => false,
                'message' => "Trop de tentatives. Veuillez réessayer dans {$minutes} minute(s).",
            ], 429);
        }

        RateLimiter::hit($key, 900); // 15 minutes

        // Generate token
        $token = Str::random(64);

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Store new token
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // Build reset URL (frontend URL)
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $resetUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        // Send email
        Mail::to($email)->send(new ResetPasswordMail($token, $email, $resetUrl));

        return response()->json([
            'success' => true,
            'message' => 'Un email de réinitialisation a été envoyé à votre adresse.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/reset-password",
     *     operationId="resetPassword",
     *     tags={"Authentication"},
     *     summary="Réinitialiser le mot de passe",
     *     description="Finalise la réinitialisation du mot de passe avec le token reçu par email. Le token est valide pendant 60 minutes.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "email", "password", "password_confirmation"},
     *             @OA\Property(property="token", type="string", example="reset_token_from_email"),
     *             @OA\Property(property="email", type="string", format="email", example="utilisateur@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="NouveauMotDePasse123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NouveauMotDePasse123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe réinitialisé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Votre mot de passe a été réinitialisé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token invalide ou expiré",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le token de réinitialisation est invalide ou a expiré.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Find token record
        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$tokenRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Le token de réinitialisation est invalide ou a expiré.',
            ], 400);
        }

        // Check if token is valid
        if (!Hash::check($validated['token'], $tokenRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Le token de réinitialisation est invalide.',
            ], 400);
        }

        // Check if token has expired (60 minutes)
        $tokenExpiration = config('auth.passwords.users.expire', 60);
        $tokenCreatedAt = Carbon::parse($tokenRecord->created_at);

        if ($tokenCreatedAt->addMinutes($tokenExpiration)->isPast()) {
            // Delete expired token
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            return response()->json([
                'success' => false,
                'message' => 'Le token de réinitialisation a expiré. Veuillez faire une nouvelle demande.',
            ], 400);
        }

        // Update user password
        $user = User::where('email', $validated['email'])->first();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Delete used token
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ]);
    }
}
