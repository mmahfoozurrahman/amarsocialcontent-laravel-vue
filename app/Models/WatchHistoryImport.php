<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WatchHistoryImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_name',
        'file_type',
        'total_items_found',
        'items_selected',
        'status',
        'error_message',
    ];

    protected $casts = [
        'total_items_found' => 'integer',
        'items_selected' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WatchHistoryItem::class);
    }
}

