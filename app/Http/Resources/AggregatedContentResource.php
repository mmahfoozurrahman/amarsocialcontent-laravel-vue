<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AggregatedContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'youtube_video_id' => $this->youtube_video_id,
            'youtube_url' => $this->youtube_url,
            'title' => $this->title,
            'bengali_title' => $this->bengali_title,
            'channel_name' => $this->channel_name,
            'thumbnail_url' => $this->thumbnail_url,
            'content_type' => $this->content_type,
            'duration_seconds' => $this->duration_seconds,
            'visibility' => $this->visibility,
            'share_token' => $this->share_token,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'ai_provider' => $this->ai_provider,
            'ai_model' => $this->ai_model,
            'reading_time_minutes' => $this->reading_time_minutes,
            'tags' => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]),
            'is_bookmarked' => $user ? $this->isBookmarkedBy($user->id) : false,
            'personal_note' => $user ? $this->notes()->where('user_id', $user->id)->value('note_content') : null,
            'reading_progress' => $user ? $this->readingHistories()->where('user_id', $user->id)->first() : null,
            'article' => $this->whenLoaded('article', function () {
                return [
                    'introduction' => $this->article?->introduction,
                    'main_discussion' => $this->article?->main_discussion,
                    'important_concepts' => $this->article?->important_concepts,
                    'examples_applications' => $this->article?->examples_applications,
                    'takeaways' => $this->article?->takeaways,
                    'key_terms' => $this->article?->key_terms,
                    'raw_markdown' => $this->article?->raw_markdown,
                ];
            }),
            'has_transcript' => (bool) $this->transcript,
            'transcript' => $this->whenLoaded('transcript', function () {
                return [
                    'full_transcript' => $this->transcript?->full_transcript,
                    'language' => $this->transcript?->language,
                    'is_manual' => $this->transcript?->is_manual,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
