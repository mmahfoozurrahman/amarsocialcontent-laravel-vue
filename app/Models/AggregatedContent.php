<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AggregatedContent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'aggregated_contents';

    protected $fillable = [
        'user_id',
        'youtube_video_id',
        'youtube_url',
        'title',
        'bengali_title',
        'channel_name',
        'thumbnail_url',
        'duration_seconds',
        'published_at',
        'content_type',
        'visibility',
        'share_token',
        'status',
        'error_message',
        'ai_provider',
        'ai_model',
        'reading_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'reading_time_minutes' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transcript(): HasOne
    {
        return $this->hasOne(ContentTranscript::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ContentChunk::class)->orderBy('chunk_index');
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(ContentEmbedding::class);
    }

    public function article(): HasOne
    {
        return $this->hasOne(ContentArticle::class);
    }

    public function processingLogs(): HasMany
    {
        return $this->hasMany(AIProcessingLog::class);
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'aggregated_content_tag');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PersonalNote::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ContentReadingHistory::class);
    }

    public function isBookmarkedBy(int $userId): bool
    {
        return $this->bookmarks()->where('user_id', $userId)->exists();
    }

    public function isShort(): bool
    {
        return $this->content_type === 'short';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canRetry(): bool
    {
        return in_array($this->status, ['failed', 'pending']);
    }

    /**
     * Generate or retrieve a share token for public viewing.
     */
    public function ensureShareToken(): string
    {
        if (empty($this->share_token)) {
            $this->share_token = Str::random(32);
            $this->save();
        }

        return $this->share_token;
    }
}

