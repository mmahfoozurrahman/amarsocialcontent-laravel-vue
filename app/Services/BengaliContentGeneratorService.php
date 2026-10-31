<?php

namespace App\Services;

use App\Models\AggregatedContent;
use App\Models\ContentArticle;
use App\Models\ContentChunk;
use App\Models\ContentTranscript;
use App\Services\AI\AIServiceManager;
use Illuminate\Support\Facades\DB;
use Throwable;

class BengaliContentGeneratorService
{
    const CHUNK_SIZE_CHARS = 8000;

    public function __construct(
        protected AIServiceManager $aiManager
    ) {}

    /**
     * Process an aggregated content record from transcript to final Bengali article.
     */
    public function processContent(AggregatedContent $content, ?string $manualTranscript = null): AggregatedContent
    {
        try {
            // 1. Check or save transcript
            if ($manualTranscript) {
                $content->update(['status' => 'processing_transcript']);

                ContentTranscript::updateOrCreate(
                    ['aggregated_content_id' => $content->id],
                    [
                        'full_transcript' => trim($manualTranscript),
                        'language' => 'manual',
                        'is_manual' => true,
                    ]
                );
            }

            $transcriptRecord = $content->transcript;
            if (!$transcriptRecord || empty($transcriptRecord->full_transcript)) {
                $content->update([
                    'status' => 'failed',
                    'error_message' => 'কোনো ট্রান্সক্রিপ্ট পাওয়া যায়নি। অনুগ্রহ করে ম্যানুয়ালি ট্রান্সক্রিপ্ট প্রদান করুন।',
                ]);
                return $content;
            }

            $fullTranscript = $transcriptRecord->full_transcript;

            // 2. Divide into manageable chunks if needed and persist chunks
            $this->chunkAndSaveTranscript($content, $fullTranscript);

            // 3. Update status to generating_bengali
            $content->update(['status' => 'generating_bengali']);

            // 4. Invoke AI Generation Pipeline (Primary with Fallback)
            $metadata = [
                'title' => $content->title,
                'channel_name' => $content->channel_name,
                'content_type' => $content->content_type,
            ];

            $aiResult = $this->aiManager->generateBengaliContent(
                transcript: $fullTranscript,
                metadata: $metadata,
                user: $content->user,
                content: $content
            );

            // 5. Save Article & Complete Aggregation in DB Transaction
            DB::transaction(function () use ($content, $aiResult) {
                ContentArticle::updateOrCreate(
                    ['aggregated_content_id' => $content->id],
                    [
                        'introduction' => $aiResult['introduction'],
                        'main_discussion' => $aiResult['main_discussion'],
                        'important_concepts' => $aiResult['important_concepts'],
                        'examples_applications' => $aiResult['examples_applications'],
                        'takeaways' => $aiResult['takeaways'],
                        'key_terms' => $aiResult['key_terms'],
                        'raw_markdown' => $aiResult['raw_markdown'],
                    ]
                );

                $content->update([
                    'bengali_title' => $aiResult['bengali_title'],
                    'reading_time_minutes' => $aiResult['reading_time_minutes'] ?? 1,
                    'ai_provider' => $aiResult['provider'] ?? 'gemini',
                    'ai_model' => $aiResult['model'] ?? 'unknown',
                    'status' => 'completed',
                    'error_message' => null,
                ]);

                // Ensure channel name is tagged by default
                if (!empty($content->channel_name)) {
                    $channelTag = \App\Models\Tag::firstOrCreate(
                        ['user_id' => $content->user_id, 'name' => trim($content->channel_name)],
                        ['slug' => \Illuminate\Support\Str::slug(trim($content->channel_name))]
                    );
                    $content->tags()->syncWithoutDetaching([$channelTag->id]);
                }
            });

            // 6. Generate and store semantic embeddings for search indexing
            try {
                app(\App\Services\Search\SemanticSearchService::class)->indexContent($content);
            } catch (\Throwable $e) {
                \Log::warning('Automatic embedding indexing failed: ' . $e->getMessage());
            }

            return $content->fresh(['article', 'transcript', 'chunks']);
        } catch (Throwable $e) {
            $content->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $content->fresh();
        }
    }

    /**
     * Split long transcripts into logical chunks without breaking words.
     */
    protected function chunkAndSaveTranscript(AggregatedContent $content, string $transcript): void
    {
        // Clear previous chunks on re-processing
        ContentChunk::where('aggregated_content_id', $content->id)->delete();

        $words = preg_split('/\s+/u', $transcript);
        $currentChunk = [];
        $currentLength = 0;
        $chunkIndex = 0;

        foreach ($words as $word) {
            $wordLen = mb_strlen($word);
            if ($currentLength + $wordLen > self::CHUNK_SIZE_CHARS && !empty($currentChunk)) {
                $chunkText = implode(' ', $currentChunk);
                ContentChunk::create([
                    'aggregated_content_id' => $content->id,
                    'chunk_index' => $chunkIndex++,
                    'original_text' => $chunkText,
                    'token_count' => (int) ceil(count($currentChunk) * 1.3),
                ]);

                $currentChunk = [$word];
                $currentLength = $wordLen;
            } else {
                $currentChunk[] = $word;
                $currentLength += $wordLen + 1;
            }
        }

        if (!empty($currentChunk)) {
            $chunkText = implode(' ', $currentChunk);
            ContentChunk::create([
                'aggregated_content_id' => $content->id,
                'chunk_index' => $chunkIndex,
                'original_text' => $chunkText,
                'token_count' => (int) ceil(count($currentChunk) * 1.3),
            ]);
        }
    }
}

