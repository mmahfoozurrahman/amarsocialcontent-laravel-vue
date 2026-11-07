<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'aggregated_content_id' => $this->aggregated_content_id,
            'title' => $this->title,
            'content' => $this->content,
            'sort_order' => $this->sort_order,
            'visibility' => $this->visibility,
            'is_completed' => (bool) $this->is_completed,
            'is_linked_to_article' => $this->isLinkedToArticle(),
            'aggregated_content' => $this->whenLoaded('aggregatedContent', function () {
                if (!$this->aggregatedContent) return null;
                return [
                    'id' => $this->aggregatedContent->id,
                    'title' => $this->aggregatedContent->title,
                    'bengali_title' => $this->aggregatedContent->bengali_title,
                    'youtube_video_id' => $this->aggregatedContent->youtube_video_id,
                    'thumbnail_url' => $this->aggregatedContent->thumbnail_url,
                    'content_type' => $this->aggregatedContent->content_type,
                    'reading_time_minutes' => $this->aggregatedContent->reading_time_minutes,
                    'article' => $this->aggregatedContent->relationLoaded('article') ? [
                        'raw_markdown' => $this->aggregatedContent->article?->raw_markdown,
                        'introduction' => $this->aggregatedContent->article?->introduction,
                        'main_discussion' => $this->aggregatedContent->article?->main_discussion,
                        'important_concepts' => $this->aggregatedContent->article?->important_concepts,
                        'examples_applications' => $this->aggregatedContent->article?->examples_applications,
                        'takeaways' => $this->aggregatedContent->article?->takeaways,
                        'key_terms' => $this->aggregatedContent->article?->key_terms,
                    ] : null,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

