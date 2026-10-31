<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIProcessingLog extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'ai_processing_logs';

    protected $fillable = [
        'user_id',
        'aggregated_content_id',
        'provider',
        'model',
        'operation_type',
        'prompt_tokens',
        'completion_tokens',
        'latency_ms',
        'status',
        'error_details',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'latency_ms' => 'integer',
            'created_at' => 'datetime',
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

