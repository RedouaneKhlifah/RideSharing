<?php

namespace App\Http\Requests\AuthRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SignUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
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
            'role' => 'required|in:regular,driver',
            'car_model' => 'required_if:role,driver|string|max:255',
            'matricule' => 'required_if:role,driver|string|max:255',
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
            "role.in" => "The role must be either 'regular' or 'driver'.",
        ];
    }
}