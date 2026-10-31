<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'cover_image' => $this->cover_image,
            'visibility' => $this->visibility,
            'sort_order' => $this->sort_order,
            'chapters_count' => $this->chapters()->count(),
            'lessons_count' => $this->lessons()->count(),
            'progress_percentage' => $this->progressPercentage(),
            'chapters' => ChapterResource::collection($this->whenLoaded('chapters')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

