<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreMonitorRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048', 'unique:monitors,url'],
            'check_interval' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'threshold' => ['sometimes', 'integer', 'min:1'],
        ];
    }


    /**
     * Get custom error messages for validation failures.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'The URL field is required.',
            'url.url' => 'The URL must be a valid URL.',
            'url.max' => 'The URL may not be greater than 2048 characters.',
            'url.unique' => 'The URL has already been taken.',
            'check_interval.integer' => 'The check interval must be an integer.',
            'check_interval.min' => 'The check interval must be at least 1 minute.',
            'check_interval.max' => 'The check interval may not be greater than 60 minutes.',
            'threshold.integer' => 'The threshold must be an integer.',
            'threshold.min' => 'The threshold must be at least 1.',
        ];
    }
}
