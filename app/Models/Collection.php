<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'visibility',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($collection) {
            if (empty($collection->slug)) {
                $base = Str::slug($collection->title) ?: 'collection';
                $slug = $base;
                $counter = 1;
                while (static::where('user_id', $collection->user_id)->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $counter++;
                }
                $collection->slug = $slug;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort_order');
    }

    public function notes(): HasManyThrough
    {
        return $this->hasManyThrough(Note::class, Section::class);
    }

    public function progressPercentage(): int
    {
        $total = $this->notes()->count();
        if ($total === 0) return 0;
        $completed = $this->notes()->where('is_completed', true)->count();
        return (int) round(($completed / $total) * 100);
    }
}

