<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'একটি সঠিক ইমেইল এড্রেস প্রদান করুন।',
            'email.email' => 'সঠিক ফরম্যাটের ইমেইল এড্রেস লিখুন।',
            'name.max' => 'নাম সর্বোচ্চ ১২০ অক্ষরের মধ্যে হতে হবে।',
        ];
    }
}

