<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Attempt to authenticate the user using JWT.
     *
     * @param array $credentials
     * @return bool
     */
    public function attemptAuthentication(array $credentials): bool
    {
        try {
            // Attempt to authenticate the user with the given credentials
            return Auth::attempt($credentials);
        } catch (JWTException $e) {
            // Handle JWT exceptions
            return false;
        }
    }

    /**
     * Check if the request is rate-limited.
     *
     * @param string $throttleKey
     * @return bool
     */
    public function isRateLimited(string $throttleKey): bool
    {
        return RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts());
    }

    /**
     * Handle rate-limited requests.
     *
     * @param string $throttleKey
     * @return JsonResponse
     */
    public function handleRateLimit(string $throttleKey): JsonResponse
    {
        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($throttleKey);

        return response()->json([
            'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
        ], 429);
    }

    /**
     * Clear rate limiting for the request.
     *
     * @param string $throttleKey
     */
    public function clearRateLimiting(string $throttleKey): void
    {
        RateLimiter::clear($throttleKey);
    }

    /**
     * Handle user creation
     *
     * @param array $data
     * @return User
     */

     public function createUser(array $data): User
     {
         return User::create([
             'name' => $data['name'],
             'email' => $data['email'],
             'password' => bcrypt($data['password']),
             'phone' => $data['phone'],
             'role' => $data['role'],
         ]);
     }
    /**
     * Store user photo and return the path.
     *
     * @param \Illuminate\Http\UploadedFile $photo
     * @return string
     */
    public function storeUserPhoto($photo): string
    {
        // Store photo in users/photos under the public disk
        $path = $photo->store('users/photos', 'public');
    
        // This will return something like 'users/photos/filename.png'
        return 'storage/' . $path;
    }

    /**
     * Generate a JWT token for the authenticated user.
     *
     * @return JsonResponse
     */
    public function generateAuthenticationResponse(): JsonResponse
    {
        try {
            $user = Auth::user();
            $token = JWTAuth::fromUser($user); // Create the JWT token
            $refreshToken = $this->generateRefreshToken($user);



            return response()->json([
                'message' => 'Authentication successful',
                'user' => $user,
                'token' => $token, // Include JWT token in the response
                'refresh_token' => $refreshToken, // Include refresh token in the response
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not create token',
            ], 500);
        }
    }

    /**
     * Increment rate limiting for the request.
     *
     * @param string $throttleKey
     */
    public function incrementRateLimiting(string $throttleKey): void
    {
        RateLimiter::hit($throttleKey);
    }

    /**
     * Handle failed authentication attempts.
     *
     * @return JsonResponse
     */
    public function handleFailedAuthentication(): JsonResponse
    {
        return response()->json([
            'message' => trans('messages.invalid_credentials'),
        ], 401);
    }

    /**
     * Get the throttle key for the request.
     *
     * @param string $email
     * @param string $ip
     * @return string
     */
    public function throttleKey(string $email, string $ip): string
    {
        return mb_strtolower($email) . '|' . $ip;
    }

    /**
     * Get the maximum number of login attempts allowed.
     *
     * @return int
     */
    protected function maxAttempts(): int
    {
        return 10; // Adjust as needed
    }


        /**
     * Generate refresh token
     */
    protected function generateRefreshToken(User $user): string
    {
        $refreshToken = bin2hex(random_bytes(32));
        Cache::put('refresh_token:'.$refreshToken, $user->id, now()->addDays(30));
        return $refreshToken;
    } 
    

    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): JsonResponse
    {
        if (!$userId = Cache::get('refresh_token:'.$refreshToken)) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        if (!$user = User::where('id', $userId)->first()) {
            return response()->json(['message' => 'User not found'], 404);
        }

        Auth::setUser($user);
        $newToken = JWTAuth::fromUser($user);
        $newRefreshToken = $this->generateRefreshToken($user);
        Cache::forget('refresh_token:'.$refreshToken);

        return response()->json([
            'message' => 'ReAuthentication successful',
            'access_token' => $newToken,
            'refresh_token' => $newRefreshToken,
            'user' => $user,
            
        ]);
    }

    /**
     * Logout user and invalidate tokens
     */
    public function logout(string $refreshToken): JsonResponse
    {
        try {
            // Invalidate the JWT token if it exists
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::invalidate($token);
            }
            
            // Remove the refresh token from cache
            Cache::forget('refresh_token:'.$refreshToken);
            
            // Logout the user
            Auth::logout();
            
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Failed to logout', 
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to logout', 
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Generate a random 4-digit verification code and store it in the database
     *
     * @param User $user
     * @return string
     */
    public function generateEmailVerificationCode(User $user): string
    {
        // Generate a random 4-digit code
        $verificationCode = sprintf('%04d', random_int(0, 9999));
        
        // Store the code in the database with an expiration time
        DB::table('email_verifications')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'code' => $verificationCode,
                'expires_at' => now()->addHour(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        return $verificationCode;
    }

    /**
     * Verify the email with the provided verification code
     *
     * @param int $userId
     * @param string $verificationCode
     * @return array
     */
    public function verifyEmail(int $userId, string $verificationCode): array
    {
        // Check if the verification code exists and is valid
        $verification = $this->verifyCode($userId, $verificationCode);
        
        if (!$verification) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification code'
            ];
        }
        
        // Mark the user's email as verified
        $user = User::find($userId);
        $user->email_verified_at = now();
        $user->save();
        
        // Delete the verification code
        DB::table('email_verifications')
            ->where('user_id', $userId)
            ->delete();
        
        return [
            'success' => true
        ];
    }

    public function verifyCode(string $id, string $verificationCode): bool
    {

        // Check if the verification code exists and is valid
        $verification = DB::table('email_verifications')
            ->where('user_id',  $id)
            ->where('code', $verificationCode)
            ->where('expires_at', '>', now())
            ->first();
            

            return $verification ? true : false;
    }

    public function createResetToken(string $email): string
    {
        // Generate a random token
        $token = Str::random(64);

        // Store it in cache for 15 minutes
        Cache::put('reset_password_token_' . $token, $email, now()->addMinutes(15));

        return $token;
    }

    public function validateResetToken(string $token): ?string
    {
        // Retrieve the email from cache
        return Cache::get('reset_password_token_' . $token);
    }

    public function invalidateResetToken(string $token): void
    {
        Cache::forget('reset_password_token_' . $token);
    }
}
