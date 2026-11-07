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
            'section' => $this->whenLoaded('section', function () {
                $section = $this->section;

                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'collection' => ($section->relationLoaded('collection') && $section->collection) ? [
                        'id' => $section->collection->id,
                        'title' => $section->collection->title,
                        'slug' => $section->collection->slug,
                        'sections' => $section->collection->relationLoaded('sections')
                            ? $section->collection->sections->map(fn ($s) => [
                                'id' => $s->id,
                                'title' => $s->title,
                                'notes' => $s->relationLoaded('notes')
                                    ? $s->notes->map(fn ($n) => [
                                        'id' => $n->id,
                                        'title' => $n->title,
                                        'is_completed' => (bool) $n->is_completed,
                                    ])->values()
                                    : [],
                            ])->values()
                            : [],
                    ] : null,
                ];
            }),
            'aggregated_content' => $this->whenLoaded('aggregatedContent', function () {
                if (! $this->aggregatedContent) {
                    return null;
                }

                return [
                    'id' => $this->aggregatedContent->id,
                    'title' => $this->aggregatedContent->title,
                    'bengali_title' => $this->aggregatedContent->bengali_title,
                    'youtube_video_id' => $this->aggregatedContent->youtube_video_id,
                    'thumbnail_url' => $this->aggregatedContent->thumbnail_url,
                    'content_type' => $this->aggregatedContent->content_type,
                    'reading_time_minutes' => $this->aggregatedContent->reading_time_minutes,
                    'tags' => $this->aggregatedContent->relationLoaded('tags')
                        ? $this->aggregatedContent->tags->map(fn ($tag) => [
                            'id' => $tag->id,
                            'name' => $tag->name,
                            'slug' => $tag->slug,
                        ])->values()
                        : [],
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
