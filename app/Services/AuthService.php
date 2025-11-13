<?php

namespace App\Services;

use App\Models\User;
use App\Models\PhotographerProfile;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        // Create the user
        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'account_type' => $data['account_type'],
            'is_verified' => false,
            'is_active' => true,
        ]);

        // If user is a photographer, create a photographer profile automatically
        if ($user->isPhotographer()) {
            $username = $this->generateUniqueUsername($data['first_name'], $data['last_name']);

            PhotographerProfile::create([
                'user_id' => $user->id,
                'username' => $username,
                'display_name' => $user->full_name,
                'status' => 'pending', // Requires admin approval
                'commission_rate' => 80.00, // 80% for photographer, 20% for platform
            ]);
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($user);

        // Send welcome email
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Exception $e) {
            // Log error but don't fail registration
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
        }

        return [
            'user' => $user->load('photographerProfile'),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // Convert minutes to seconds
        ];
    }

    /**
     * Login a user and return JWT token.
     *
     * @param string $email
     * @param string $password
     * @param bool $rememberMe
     * @return array
     * @throws \Exception
     */
    public function login(string $email, string $password, bool $rememberMe = false): array
    {
        $credentials = compact('email', 'password');

        // Attempt to verify credentials and create token
        if (!$token = JWTAuth::attempt($credentials)) {
            throw new \Exception('Les identifiants sont incorrects.');
        }

        // Get authenticated user
        $user = auth()->user();

        // Check if account is active
        if (!$user->is_active) {
            throw new \Exception('Votre compte a été désactivé. Veuillez contacter le support.');
        }

        // Update last login timestamp
        $user->update(['last_login' => now()]);

        // If remember me, set longer TTL
        if ($rememberMe) {
            $ttl = config('jwt.refresh_ttl'); // 14 days
            JWTAuth::factory()->setTTL($ttl);
        }

        return [
            'user' => $user->load('photographerProfile'),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Logout the user (invalidate token).
     *
     * @return void
     */
    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    /**
     * Refresh the JWT token.
     *
     * @return string
     */
    public function refresh(): string
    {
        return JWTAuth::refresh(JWTAuth::getToken());
    }

    /**
     * Get authenticated user with relationships.
     *
     * @return User
     */
    public function me(): User
    {
        return auth()->user()->load('photographerProfile');
    }

    /**
     * Generate a unique username from first and last name.
     *
     * @param string $firstName
     * @param string $lastName
     * @return string
     */
    private function generateUniqueUsername(string $firstName, string $lastName): string
    {
        // Remove accents and special characters
        $firstName = $this->slugify($firstName);
        $lastName = $this->slugify($lastName);

        // Start with firstname.lastname
        $baseUsername = strtolower($firstName . '.' . $lastName);
        $username = $baseUsername;
        $counter = 1;

        // Check if username exists, if so add a number
        while (PhotographerProfile::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Slugify a string (remove accents, special chars, etc.).
     *
     * @param string $text
     * @return string
     */
    private function slugify(string $text): string
    {
        // Replace non-letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim
        $text = trim($text, '-');

        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // Lowercase
        $text = strtolower($text);

        return $text;
    }
}
