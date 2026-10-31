<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'otp_hash',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Determine if the OTP is currently expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Determine if the OTP has already been verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }
}

