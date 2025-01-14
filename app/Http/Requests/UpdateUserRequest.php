<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'image' => ['image', 'mimes:png,jpg,jpeg', 'max:2048']
        ];
    }

    public function messages(): array
    {
        return [
            'image.image' => 'The file must be an image',
            'image.mimes' => 'The image must be a file of type: png, jpg, jpeg',
            'image.max' => 'The image may not be greater than 2MB'
        ];
    }
}
