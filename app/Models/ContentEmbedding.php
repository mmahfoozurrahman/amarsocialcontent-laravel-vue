<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentEmbedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'aggregated_content_id',
        'content_chunk_id',
        'embedding_vector',
        'dimensions',
        'model_name',
    ];

    protected $casts = [
        'embedding_vector' => 'array',
        'dimensions' => 'integer',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(AggregatedContent::class, 'aggregated_content_id');
    }

    public function chunk(): BelongsTo
    {
        return $this->belongsTo(ContentChunk::class, 'content_chunk_id');
    }
}

