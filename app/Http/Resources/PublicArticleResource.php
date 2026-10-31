<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array for public quarantined consumption.
     */
    public function toArray(Request $request): array
    {
        return [
            'share_token' => $this->share_token,
            'title' => $this->title,
            'bengali_title' => $this->bengali_title,
            'channel_name' => $this->channel_name,
            'youtube_video_id' => $this->youtube_video_id,
            'thumbnail_url' => $this->thumbnail_url,
            'content_type' => $this->content_type,
            'reading_time_minutes' => $this->reading_time_minutes,
            'article' => [
                'introduction' => $this->article?->introduction,
                'main_discussion' => $this->article?->main_discussion,
                'important_concepts' => $this->article?->important_concepts,
                'examples_applications' => $this->article?->examples_applications,
                'takeaways' => $this->article?->takeaways,
                'key_terms' => $this->article?->key_terms,
                'raw_markdown' => $this->article?->raw_markdown,
            ],
            'tags' => $this->tags->map(fn ($tag) => [
                'name' => $tag->name,
            ]),
            'created_at' => $this->created_at?->toFormattedDateString(),
        ];
    }
}

