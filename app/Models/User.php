<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'is_active',
        'email_verified_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function searchHistories(): HasMany
    {
        return $this->hasMany(SearchHistory::class)->latest();
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * User's aggregated contents.
     */
    public function aggregatedContents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AggregatedContent::class);
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function personalNotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PersonalNote::class);
    }

    public function bookmarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function readingHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ContentReadingHistory::class);
    }

    public function courses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function watchHistoryImports(): HasMany
    {
        return $this->hasMany(WatchHistoryImport::class)->latest();
    }

    public function watchHistoryItems(): HasMany
    {
        return $this->hasMany(WatchHistoryItem::class)->latest('watched_at');
    }

    public function aiSetting(): HasOne
    {
        return $this->hasOne(UserAiSetting::class);
    }
}
