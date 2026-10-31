<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreYouTubeContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'youtube_url' => ['required', 'string', 'url', 'max:512'],
            'manual_transcript' => ['nullable', 'string', 'min:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'youtube_url.required' => 'ইউটিউব ভিডিও বা শর্টসের ইউআরএল প্রদান করুন।',
            'youtube_url.url' => 'সঠিক ফরম্যাটের ওয়েব লিঙ্ক লিখুন।',
            'manual_transcript.min' => 'ম্যানুয়াল ট্রান্সক্রিপ্টে অন্তত ১০টি অক্ষর থাকতে হবে।',
        ];
    }
}

