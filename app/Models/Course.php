<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Course extends Model
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

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $base = Str::slug($course->title) ?: 'course';
                $slug = $base;
                $counter = 1;
                while (static::where('user_id', $course->user_id)->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $counter++;
                }
                $course->slug = $slug;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('sort_order');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Chapter::class);
    }

    public function progressPercentage(): int
    {
        $total = $this->lessons()->count();
        if ($total === 0) return 0;
        $completed = $this->lessons()->where('is_completed', true)->count();
        return (int) round(($completed / $total) * 100);
    }
}

