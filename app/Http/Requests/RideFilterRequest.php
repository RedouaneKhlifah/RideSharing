<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;


class RideFilterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'departure_location' => 'sometimes|string|max:255',
            'destination' => 'sometimes|string|max:255',
            'departure_date' => 'sometimes|date_format:Y-m-d',
            "available_seats" => "sometimes|integer|min:1",
        ];
    }
}