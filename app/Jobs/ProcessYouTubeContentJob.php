<?php

namespace App\Jobs;

use App\Models\AggregatedContent;
use App\Models\ContentTranscript;
use App\Services\BengaliContentGeneratorService;
use App\Services\YouTubeTranscriptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessYouTubeContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public AggregatedContent $content,
        public ?string $manualTranscript = null
    ) {}

    public function handle(
        YouTubeTranscriptService $youtubeService,
        BengaliContentGeneratorService $generatorService
    ): void {
        try {
            // If manual transcript is provided
            if ($this->manualTranscript) {
                $generatorService->processContent($this->content, $this->manualTranscript);

                return;
            }

            // Step 1: If transcript does not exist yet, extract it
            if (! $this->content->transcript) {
                $this->content->update(['status' => 'extracting_transcript']);

                $transcriptData = $youtubeService->extractTranscript($this->content->youtube_video_id);

                if ($transcriptData) {
                    ContentTranscript::create([
                        'aggregated_content_id' => $this->content->id,
                        'full_transcript' => $transcriptData['full_transcript'],
                        'language' => $transcriptData['language'] ?? 'en',
                        'is_manual' => false,
                    ]);
                    $this->content->load('transcript');
                } else {
                    $this->content->update([
                        'status' => 'failed',
                        'error_message' => 'এই ভিডিওটির অটোমেটিক ক্যাপশন বা সাবটাইটেল উন্মুক্ত নেই। অনুগ্রহ করে "ম্যানুয়াল ট্রান্সক্রিপ্ট" অপশন ব্যবহার করুন।',
                    ]);

                    return;
                }
            }

            // Step 2: Generate mentor-style Bengali article
            $generatorService->processContent($this->content);
        } catch (Throwable $e) {
            $this->content->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
