<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRideRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'driver' && $user->email_verified_at !== null;
    }
    
    protected function failedAuthorization()
    {
        throw new AuthorizationException('Only drivers are allowed to create a ride.');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'departure_location' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_time' => 'required|date_format:Y-m-d\TH:i:s\Z|after:now',
            'available_seats' => 'required|integer|min:1',
        ];
    }
}
