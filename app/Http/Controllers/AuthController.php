<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequests\RefreshTokenRequest;
use App\Http\Requests\AuthRequests\sendVerificationCodeRequest;
use App\Http\Requests\AuthRequests\SignInRequest;
use App\Http\Requests\AuthRequests\VerifyCodeRequest;
use App\Http\Requests\AuthRequests\VerifyEmailRequest;
use App\Models\User;
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
use Illuminate\Http\Request;

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
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            // Avoid revealing existence of the user
            return response()->json(['message' => 'Invalid email or verification code'], 400);
        }
    
        // Optional: Check if already verified
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified'], 400);
        }
    
        $result = $this->authService->verifyEmail($user->id, $request->verification_code);
    
        if ($result['success']) {
            return response()->json(['message' => 'Email verified successfully'], 200);
        }
    
        return response()->json(['message' => $result['message'] ?? 'Verification failed'], 400);
    }

        
    
    /**
     * Resend verification code email.
     * 
     * @return JsonResponse
     */
    public function sendVerificationCode(sendVerificationCodeRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();
    
        if (!$user || $user->email_verified_at) {
            // Avoid revealing user existence
            return response()->json(['message' => 'If your email is registered, a verification code has been sent.'], 200);
        }
    
        // Optionally rate-limit here
    
        $verificationCode = $this->authService->generateEmailVerificationCode($user);
    
        event(new UserRegistered($user, $verificationCode)); // Better event name
    
        return response()->json(['message' => 'Verification code sent to your email'], 200);
    }

    public function verifyCode(VerifyCodeRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            // Avoid revealing existence of the user
            return response()->json(['message' => 'Invalid email or verification code'], 400);
        }

        $verificationCode = $this->authService->verifyCode($user->email, $request->verification_code);
    
        if ($verificationCode) {
            return response()->json(['message' => 'Verification code is valid'], 200);
        }
    
        return response()->json(['message' => 'Invalid or expired verification code'], 400);
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