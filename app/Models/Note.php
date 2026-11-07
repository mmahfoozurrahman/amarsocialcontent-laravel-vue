<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'aggregated_content_id',
        'title',
        'content',
        'sort_order',
        'visibility',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_completed' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function aggregatedContent(): BelongsTo
    {
        return $this->belongsTo(AggregatedContent::class, 'aggregated_content_id');
    }

    public function isLinkedToArticle(): bool
    {
        return !empty($this->aggregated_content_id);
    }
}

