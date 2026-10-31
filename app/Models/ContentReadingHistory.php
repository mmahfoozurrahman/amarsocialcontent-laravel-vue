<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentReadingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'aggregated_content_id',
        'seconds_spent',
        'is_completed',
        'last_read_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'seconds_spent' => 'integer',
            'is_completed' => 'boolean',
            'view_count' => 'integer',
            'last_read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(AggregatedContent::class, 'aggregated_content_id');
    }
}

