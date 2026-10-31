<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'aggregated_content_id',
        'introduction',
        'main_discussion',
        'important_concepts',
        'examples_applications',
        'takeaways',
        'key_terms',
        'raw_markdown',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(AggregatedContent::class, 'aggregated_content_id');
    }
}

