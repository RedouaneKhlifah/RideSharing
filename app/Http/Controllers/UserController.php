<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Services\AuthService;

class UserController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    /**
     * Get authenticated user's profile
     */
    public function profile(): JsonResponse
    {
        return response()->json(Auth::user());
    }
    
    /**
     * Update user profile
     */
    
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();
    
        $validatedData = $request->validated();

        if ($request->hasFile('photo')) {
            $validatedData['photo'] = $this->authService->storeUserPhoto($request->file('photo'));
        }
    
        $user->update($validatedData);
    
        return response()->json($user);
    }
    
    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = Auth::user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }
        
        $user->password = Hash::make($request->new_password);
        $user->save();
        
        return response()->json(['message' => 'Password changed successfully']);
    }
}