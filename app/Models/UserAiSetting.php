<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'primary_provider',
        'groq_api_key',
        'gemini_api_key',
        'groq_model',
        'gemini_model',
    ];

    /**
     * Use AES-256 encrypted casts for sensitive AI keys.
     */
    protected $casts = [
        'groq_api_key' => 'encrypted',
        'gemini_api_key' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasGroqKey(): bool
    {
        return !empty($this->groq_api_key);
    }

    public function hasGeminiKey(): bool
    {
        if (empty($this->gemini_api_key)) {
            return false;
        }

        // Prevent accidental misconfiguration where a Groq key (gsk_...) was pasted into Gemini key field
        if (str_starts_with(trim($this->gemini_api_key), 'gsk_')) {
            return false;
        }

        return true;
    }

    public function hasAnyKey(): bool
    {
        return $this->hasGroqKey() || $this->hasGeminiKey();
    }

    public function getMaskedGroqKey(): ?string
    {
        if (empty($this->groq_api_key)) {
            return null;
        }
        $len = strlen($this->groq_api_key);
        if ($len <= 8) {
            return '••••••••';
        }
        return substr($this->groq_api_key, 0, 4) . '••••••••' . substr($this->groq_api_key, -4);
    }

    public function getMaskedGeminiKey(): ?string
    {
        if (empty($this->gemini_api_key)) {
            return null;
        }
        $len = strlen($this->gemini_api_key);
        if ($len <= 8) {
            return '••••••••';
        }
        return substr($this->gemini_api_key, 0, 4) . '••••••••' . substr($this->gemini_api_key, -4);
    }
}

