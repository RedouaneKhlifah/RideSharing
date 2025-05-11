<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequests\RefreshTokenRequest;
use App\Http\Requests\AuthRequests\sendVerificationCodeRequest;
use App\Http\Requests\AuthRequests\SignInRequest;
use App\Http\Requests\AuthRequests\VerifyCodeRequest;
use App\Http\Requests\AuthRequests\VerifyEmailRequest;
use App\Http\Requests\AuthRequests\VerifyResetPasswordCodeRequest;
use App\Http\Requests\AuthRequests\ResetPasswordRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

use App\Http\Requests\AuthRequests\SignUpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Events\UserRegistered;

class AuthController extends Controller
{
    /**
     * The authentication service.
     *ss
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
    
        if (!$user) {
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

        $verificationCode = $this->authService->verifyCode($user->id, $request->verification_code);
    

        if (!$verificationCode) {
                    return response()->json(['message' => 'Invalid or expired verification code'], 400);
        }

        return response()->json(['message' => 'Verification code is valid'], 200);

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

    /**
     * verify reset password code
    */

    public function verifyResetPasswordCode(VerifyResetPasswordCodeRequest $request): JsonResponse
    {

        $user = User::where('email', $request->email)->first();

        $verificationCode = $this->authService->verifyCode($user->id, $request->verification_code);

        Log::info('User id: ' . $request->id);
        Log::info('Verifying reset password code for user: ' . $user->email);
        Log::info('Verification code: ' . $request->verification_code);
        Log::info('Verification result: ' . ($verificationCode ? 'Valid' : 'Invalid'));
        
        if (!$user || !$verificationCode ) {
            return response()->json(['message' => 'Invalid email or verification code'],  400);
        }

        // Optionally generate a short-lived reset token (UUID or random string)
        $resetToken = $this->authService->createResetToken($user->email);

        return response()->json([
            'message' => 'Verification code is valid',
            'reset_token' => $resetToken,
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // Check if the reset token is valid
        $email = $this->authService->validateResetToken($request->reset_token);
        if (!$email || $email !== $request->email) {
            return response()->json(['message' => 'Invalid or expired reset token'], 400);
        }
        // Find the user by email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update the user's password
        $user->password = bcrypt($request->password);
        $user->save();
        // Clear the reset token cache
        return response()->json(['message' => 'Password reset successfully']);
    }


}