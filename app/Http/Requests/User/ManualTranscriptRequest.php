<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ManualTranscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transcript' => ['required', 'string', 'min:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'transcript.required' => 'ট্রান্সক্রিপ্ট টেক্সট আবশ্যক।',
            'transcript.min' => 'বিশ্লেষণের জন্য ট্রান্সক্রিপ্ট কমপক্ষে ২০ অক্ষরের হতে হবে।',
        ];
    }
}

