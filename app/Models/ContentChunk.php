<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'aggregated_content_id',
        'chunk_index',
        'original_text',
        'translated_text',
        'token_count',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'token_count' => 'integer',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(AggregatedContent::class, 'aggregated_content_id');
    }
}

