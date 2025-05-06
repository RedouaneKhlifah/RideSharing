<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->email_verified_at !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required |string|max:255',
            'phone' => [
                'required',
                'string',
                // 'regex:/^(?:\+212|0)([5-7]\d{8})$/',
                'max:20',
            ],
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sex' => 'required|in:male,female,other',
            'car_model' => 'required:role,driver|string|max:255',
            'matricule' => 'required:role,driver|string|max:255',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number must be a valid Moroccan mobile number (starting with +212 or 0 followed by 5, 6, or 7).',
            'photo.image' => 'The photo must be an image file.',
            'photo.mimes' => 'The photo must be a file of type: jpeg, png, jpg, gif.',
            'photo.max' => 'The photo may not be greater than 2MB in size.',
            'sex.in' => "Sex must be either 'male', 'female', or 'other'.",
            'role.in' => "The role must be either 'regular' or 'driver'.",
            'car_model.required_if' => 'The car model is required when the role is driver.',
            'matricule.required_if' => 'The matricule is required when the role is driver.',
        ];
    }
}
