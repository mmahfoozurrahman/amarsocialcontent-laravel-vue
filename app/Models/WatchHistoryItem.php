<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchHistoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'watch_history_import_id',
        'user_id',
        'youtube_video_id',
        'youtube_url',
        'title',
        'channel_name',
        'channel_url',
        'content_type',
        'watched_at',
        'is_aggregated',
        'aggregated_content_id',
    ];

    protected $casts = [
        'watched_at' => 'datetime',
        'is_aggregated' => 'boolean',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(WatchHistoryImport::class, 'watch_history_import_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aggregatedContent(): BelongsTo
    {
        return $this->belongsTo(AggregatedContent::class, 'aggregated_content_id');
    }
}

