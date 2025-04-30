<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequests\RefreshTokenRequest;
use App\Http\Requests\AuthRequests\SignInRequest;
use App\Http\Requests\AuthRequests\VerifyEmailRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

use App\Http\Requests\AuthRequests\SignUpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Events\Registered;
use App\Events\UserRegistered;

class AuthController extends Controller
{
    /**
     * The authentication service.
     *
     * @var AuthService
     */
    protected AuthService $authService;

    /**
     * Create a new controller instance.
     *
     * @param AuthService $authService
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle user sign-in.
     *
     * @param SignInRequest $request
     * @return JsonResponse
     */
    public function signIn(SignInRequest $request): JsonResponse
    {
        // Generate the throttle key
        $throttleKey = $this->authService->throttleKey($request->email, $request->ip());
    
        // Ensure the request is not rate-limited
        if ($this->authService->isRateLimited($throttleKey)) {
            return $this->authService->handleRateLimit($throttleKey);
        }
    
        // Attempt to authenticate the user
        if ($this->authService->attemptAuthentication($request->only('email', 'password'))) {
            // Check if email is verified
            $user = Auth::user();
            if (!$user->email_verified_at) {
                $verificationCode = $this->authService->generateEmailVerificationCode($user);
                event(new UserRegistered($user, $verificationCode));
            }
            
            // Clear rate limiting and generate a token
            $this->authService->clearRateLimiting($throttleKey);
            return $this->authService->generateAuthenticationResponse();
        }
    
        // Increment rate limiting and return error response
        $this->authService->incrementRateLimiting($throttleKey);
        return $this->authService->handleFailedAuthentication();
    }

    /**
     * Handle user sign-up.
     * 
     * @param SignUpRequest $request
     * @return JsonResponse
     */
    public function signUp(SignUpRequest $request): JsonResponse
    {
        DB::beginTransaction();
        
        try {
            $data = $request->validated();
            
            if ($request->hasFile('photo')) {
                $data['photo'] = $this->authService->storeUserPhoto($request->file('photo'));
            }
        
            $user = $this->authService->createUser($data);
            
            // Generate and store verification code
            $verificationCode = $this->authService->generateEmailVerificationCode($user);
            
            // Dispatch event to send verification email
            event(new UserRegistered($user, $verificationCode));
            
            Auth::login($user);
            DB::commit();
            
            return $this->authService->generateAuthenticationResponse();
            
        } catch (\Exception $e) {
            DB::rollBack();
            // Delete uploaded photo if user creation failed
            if (isset($photoPath)) {
                Storage::delete($photoPath);
            }
            return response()->json(['message' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Verify user email with verification code.
     * 
     * @param VerifyEmailRequest $request
     * @return JsonResponse
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $result = $this->authService->verifyEmail($request->user_id, $request->verification_code);
        
        if ($result['success']) {
            return response()->json(['message' => 'Email verified successfully'], 200);
        }
        
        return response()->json(['message' => $result['message']], 400);
    }
    
    /**
     * Resend verification code email.
     * 
     * @return JsonResponse
     */
    public function resendVerificationCode(): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified'], 400);
        }
        
        // Generate new verification code
        $verificationCode = $this->authService->generateEmailVerificationCode($user);
        
        // Send email with verification code
        event(new UserRegistered($user, $verificationCode));
        
        Log::info('Verification code sent to user: ' . $user->email);
        
        return response()->json(['message' => 'Verification code sent to your email'], 200);
    }

    /**
     * Refresh access token
     */
    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        return $this->authService->refreshToken($request->refresh_token);
    }

    /**
     * Logout user
     */
    public function logout(RefreshTokenRequest $request): JsonResponse
    {
        return $this->authService->logout($request->refresh_token);
    }
}