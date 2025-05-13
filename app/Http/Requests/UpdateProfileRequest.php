<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    private $user;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->user = Auth::user();
    }

    public function authorize(): bool
    {
        return $this->user?->email_verified_at !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                // 'regex:/^(?:\+212|0)([5-7]\d{8})$/',
                'max:20',
            ],
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sex' => 'required|in:male,female,other',
            // Conditionally required if user is a driver
            'car_model' => [
                Rule::requiredIf($this->user && $this->user->role === 'driver'),
                'string',
                'max:255',
            ],
            'matricule' => [
                Rule::requiredIf($this->user && $this->user->role === 'driver'),
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image' => 'The photo must be an image file.',
            'photo.mimes' => 'The photo must be a file of type: jpeg, png, jpg, gif.',
            'photo.max' => 'The photo may not be greater than 2MB in size.',
            'sex.in' => "Sex must be either 'male', 'female', or 'other'.",
            'car_model.required_if' => 'The car model is required for drivers.',
            'matricule.required_if' => 'The matricule is required for drivers.',
        ];
    }
}
