<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentTranscript extends Model
{
    use HasFactory;

    protected $fillable = [
        'aggregated_content_id',
        'full_transcript',
        'language',
        'is_manual',
    ];

    protected function casts(): array
    {
        return [
            'is_manual' => 'boolean',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(AggregatedContent::class, 'aggregated_content_id');
    }
}

