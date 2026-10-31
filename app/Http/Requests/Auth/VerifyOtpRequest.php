<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'otp' => ['required', 'string', 'size:6'],
            'name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'ইমেইল এড্রেস আবশ্যক।',
            'email.email' => 'সঠিক ইমেইল এড্রেস প্রদান করুন।',
            'otp.required' => '৬-সংখ্যার ওটিপি কোড লিখুন।',
            'otp.size' => 'ওটিপি কোডটি অবশ্যই ৬ সংখ্যার হতে হবে।',
        ];
    }
}

