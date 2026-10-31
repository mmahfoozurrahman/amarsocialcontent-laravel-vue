<?php

namespace App\Services\Search;

use App\Models\AggregatedContent;
use App\Models\ContentEmbedding;
use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SemanticSearchService
{
    public function __construct(
        protected EmbeddingService $embeddingService
    ) {}

    /**
     * Perform multi-tenant hybrid / semantic / lexical search.
     */
    public function search(User $user, string $query, array $options = []): array
    {
        $rawQuery = trim($query);
        if (empty($rawQuery)) {
            return [
                'query' => '',
                'search_type' => $options['search_type'] ?? 'hybrid',
                'results' => [],
                'total' => 0,
            ];
        }

        $searchType = $options['search_type'] ?? 'hybrid'; // hybrid, semantic, fulltext
        $contentType = $options['content_type'] ?? 'all';
        $limit = max(1, min(50, (int) ($options['limit'] ?? 20)));

        $cacheKey = "search:{$user->id}:" . md5("{$rawQuery}:{$searchType}:{$contentType}:{$limit}");

        $results = Cache::remember($cacheKey, 300, function () use ($user, $rawQuery, $searchType, $contentType, $limit) {
            return $this->executeSearch($user, $rawQuery, $searchType, $contentType, $limit);
        });

        // Record search history for user analytics & suggestions
        SearchHistory::create([
            'user_id' => $user->id,
            'query' => mb_substr($rawQuery, 0, 255),
            'search_type' => $searchType,
            'results_count' => count($results),
        ]);

        return [
            'query' => $rawQuery,
            'search_type' => $searchType,
            'results' => $results,
            'total' => count($results),
        ];
    }

    /**
     * Generate and store embedding for an aggregated article if not already present.
     */
    public function indexContent(AggregatedContent $content): void
    {
        $content->loadMissing(['article', 'transcript']);
        
        $textToIndex = $content->bengali_title . "\n\n";
        if ($content->article) {
            $textToIndex .= ($content->article->introduction ?? '') . "\n\n";
            $textToIndex .= ($content->article->main_discussion ?? '') . "\n\n";
            $textToIndex .= ($content->article->important_concepts ?? '') . "\n\n";
            $textToIndex .= ($content->article->key_terms ?? '');
        } elseif ($content->transcript) {
            $textToIndex .= $content->transcript->full_transcript;
        }

        $vector = $this->embeddingService->generateEmbedding($textToIndex);

        ContentEmbedding::updateOrCreate(
            ['aggregated_content_id' => $content->id, 'content_chunk_id' => null],
            [
                'embedding_vector' => $vector,
                'dimensions' => EmbeddingService::DIMENSIONS,
                'model_name' => EmbeddingService::MODEL_NAME,
            ]
        );
    }

    /**
     * Core search execution combining Lexical and Semantic scores.
     */
    protected function executeSearch(User $user, string $query, string $searchType, string $contentType, int $limit): array
    {
        // 1. Fetch user-scoped completed contents (Zero-trust multi-tenancy)
        $contentsQuery = AggregatedContent::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['article', 'embeddings', 'tags']);

        if ($contentType !== 'all') {
            $contentsQuery->where('content_type', $contentType);
        }

        $userContents = $contentsQuery->get();
        if ($userContents->isEmpty()) {
            return [];
        }

        // 2. Compute Lexical Match Scores
        $lexicalScores = $this->computeLexicalScores($userContents, $query);

        // 3. Compute Semantic Cosine Similarity Scores (if hybrid or semantic)
        $semanticScores = [];
        if ($searchType === 'hybrid' || $searchType === 'semantic') {
            $queryVector = $this->embeddingService->generateEmbedding($query);
            foreach ($userContents as $content) {
                // Ensure content has embedding
                $embeddingRecord = $content->embeddings->firstWhere('content_chunk_id', null);
                if (!$embeddingRecord) {
                    $this->indexContent($content);
                    $content->load('embeddings');
                    $embeddingRecord = $content->embeddings->firstWhere('content_chunk_id', null);
                }

                if ($embeddingRecord && !empty($embeddingRecord->embedding_vector)) {
                    $sim = $this->embeddingService->cosineSimilarity($queryVector, $embeddingRecord->embedding_vector);
                    $semanticScores[$content->id] = $sim;
                } else {
                    $semanticScores[$content->id] = 0.0;
                }
            }
        }

        // 4. Score Fusion and Ranking
        $scoredResults = [];
        foreach ($userContents as $content) {
            $lScore = $lexicalScores[$content->id] ?? 0.0;
            $sScore = $semanticScores[$content->id] ?? 0.0;

            if ($searchType === 'semantic') {
                $finalScore = $sScore;
            } elseif ($searchType === 'fulltext') {
                $finalScore = $lScore;
            } else {
                // Hybrid: 70% Semantic + 30% Lexical
                $finalScore = ($sScore * 0.70) + ($lScore * 0.30);
            }

            // Exclude completely irrelevant matches
            if ($finalScore > 0.08) {
                $excerpt = $this->extractHighlightedExcerpt($content, $query);
                
                $scoredResults[] = [
                    'id' => $content->id,
                    'youtube_video_id' => $content->youtube_video_id,
                    'youtube_url' => $content->youtube_url,
                    'title' => $content->title,
                    'bengali_title' => $content->bengali_title ?: $content->title,
                    'channel_name' => $content->channel_name,
                    'thumbnail_url' => $content->thumbnail_url,
                    'content_type' => $content->content_type,
                    'reading_time_minutes' => $content->reading_time_minutes,
                    'tags' => $content->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color]),
                    'score' => round($finalScore, 4),
                    'relevance_percent' => min(100, max(15, (int) round($finalScore * 100))),
                    'excerpt' => $excerpt,
                    'matched_type' => ($sScore > $lScore) ? 'semantic' : 'lexical',
                    'created_at' => $content->created_at->toIso8601String(),
                ];
            }
        }

        // Sort descending by relevance score
        usort($scoredResults, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scoredResults, 0, $limit);
    }

    /**
     * Compute lexical relevance scores across titles and raw markdown with title boost and Bengali normalization.
     */
    protected function computeLexicalScores($contents, string $query): array
    {
        $scores = [];
        $normalizedQuery = $this->normalizeText($query);
        $rawWords = preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY);
        
        $stopWords = ['এবং', 'কিন্তু', 'অথবা', 'জন্য', 'থেকে', 'করে', 'হলে', 'হয়ে', 'দিয়ে', 'আর', 'কী', 'কি', 'যে', 'সে', 'এই', 'ওই', 'এর', 'কে', 'তে', 'ও', 'and', 'the', 'or', 'in', 'on', 'at', 'to', 'for', 'of', 'with'];
        $baseWords = array_values(array_filter($rawWords, fn($w) => !in_array($w, $stopWords)));
        if (empty($baseWords)) {
            $baseWords = $rawWords;
        }

        if (empty($baseWords)) {
            foreach ($contents as $c) {
                $scores[$c->id] = 0.0;
            }
            return $scores;
        }

        // Expand query with common cross-lingual technical synonyms
        $expandedWords = $this->expandCrossLingualKeywords($baseWords);

        foreach ($contents as $content) {
            $bengaliTitle = $this->normalizeText($content->bengali_title ?? '');
            $originalTitle = $this->normalizeText($content->title ?? '');
            $markdown = $this->normalizeText($content->article->raw_markdown ?? '');

            $titleScore = 0.0;
            $bodyScore = 0.0;
            $matchedTokens = 0;

            // 1. Exact phrase match in Title (Highest relevance boost)
            if (str_contains($bengaliTitle, $normalizedQuery) || str_contains($originalTitle, $normalizedQuery)) {
                $titleScore += 1.20;
                $matchedTokens += count($baseWords);
            }

            // 2. Exact phrase match in Body
            if (str_contains($markdown, $normalizedQuery)) {
                $bodyScore += 0.40;
            }

            // 3. Word token matches with heavy Title prioritization
            foreach ($expandedWords as $word) {
                $inBengaliTitle = str_contains($bengaliTitle, $word);
                $inOriginalTitle = str_contains($originalTitle, $word);
                $inBody = str_contains($markdown, $word);

                if ($inBengaliTitle || $inOriginalTitle) {
                    $titleScore += 0.65; // High weight for title match
                    $matchedTokens++;
                }

                if ($inBody) {
                    $bodyScore += 0.12; // Contextual weight for body match
                }
            }

            if ($matchedTokens === 0 && $titleScore === 0.0 && $bodyScore === 0.0) {
                $scores[$content->id] = 0.0;
                continue;
            }

            // Title weight dominates body-only incidental mentions
            $coverageRatio = min(1.0, $matchedTokens / max(1, count($baseWords)));
            $combinedRaw = ($titleScore * 1.5) + ($bodyScore * 0.4);
            $normalizedScore = min(1.0, ($combinedRaw * 0.5) + ($coverageRatio * 0.5));

            $scores[$content->id] = round($normalizedScore, 4);
        }

        return $scores;
    }

    /**
     * Normalize Bengali text, handle decomposed Unicode variants (Nukta), and spelling variations.
     */
    public function normalizeText(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C);
        }

        // Convert decomposed Ya + Nukta (\u{09AF}\u{09BC}) to precomposed \u{09DF} (য়)
        $text = str_replace(["\u{09AF}\u{09BC}", "য\u{09BC}"], 'য়', $text);
        // Convert decomposed Dda + Nukta to ড়
        $text = str_replace(["\u{09A1}\u{09BC}", "ড\u{09BC}"], 'ড়', $text);
        // Convert decomposed Ddha + Nukta to ঢ়
        $text = str_replace(["\u{09A2}\u{09BC}", "ঢ\u{09BC}"], 'ঢ়', $text);
        // Remove zero-width formatting characters
        $text = str_replace(["\u{200C}", "\u{200D}", "\u{FEFF}"], '', $text);

        // Normalize frequent Bengali loanword spelling variants
        $text = str_replace(['সফটওয়্যার', 'সফটওযার', 'সফ্টওয়্যার', 'সফ্টওয়্যার'], 'সফটওয়্যার', $text);
        $text = str_replace(['ক্যারিয়ার', 'কেরিয়ার', 'কেরিয়ার'], 'ক্যারিয়ার', $text);
        $text = str_replace(['ডেভেলাপার', 'ডেভেলপর'], 'ডেভেলপার', $text);
        $text = str_replace(['চ্যাটবট', 'চ্যাট বট', 'চেটবট'], 'চ্যাটবট', $text);
        $text = str_replace(['ম্যাকবুক', 'ম্যক বুক', 'ম্যাক বুক'], 'ম্যাকবুক', $text);

        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * Expand common bilingual tech terms between Bengali and English.
     */
    protected function expandCrossLingualKeywords(array $words): array
    {
        $synonyms = [
            'সফটওয়্যার' => ['software'],
            'software' => ['সফটওয়্যার'],
            'ক্যারিয়ার' => ['career', 'job', 'মার্কেট'],
            'career' => ['ক্যারিয়ার'],
            'ডেভেলপার' => ['developer', 'programmer'],
            'developer' => ['ডেভেলপার'],
            'চ্যাটবট' => ['chatbot'],
            'chatbot' => ['চ্যাটবট'],
            'এজেন্ট' => ['agent'],
            'agent' => ['এজেন্ট'],
            'ম্যাকবুক' => ['macbook', 'apple'],
            'macbook' => ['ম্যাকবুক'],
            'টাচস্ক্রিন' => ['touchscreen'],
            'touchscreen' => ['টাচস্ক্রিন'],
            'ডিসপ্লে' => ['display', 'screen'],
            'display' => ['ডিসপ্লে'],
            'আর্কিটেকচার' => ['architecture'],
            'architecture' => ['আর্কিটেকচার'],
        ];

        $expanded = [];
        foreach ($words as $w) {
            $expanded[] = $w;
            if (isset($synonyms[$w])) {
                foreach ($synonyms[$w] as $syn) {
                    $expanded[] = $syn;
                }
            }
        }

        return array_unique($expanded);
    }

    /**
     * Locate the most relevant sentence or paragraph and wrap query terms with <mark> tags.
     */
    protected function extractHighlightedExcerpt(AggregatedContent $content, string $query): string
    {
        $text = $content->article->raw_markdown ?? $content->bengali_title ?? '';
        // Clean markdown headings, images, links
        $cleanText = preg_replace('/[#*_`\[\]\(\)>]/u', ' ', $text);
        $cleanText = trim(preg_replace('/\s+/u', ' ', $cleanText));

        if (empty($cleanText)) {
            return $content->bengali_title ?? '';
        }

        $normalizedQuery = $this->normalizeText($query);
        $queryWords = preg_split('/\s+/u', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY);
        $bestPos = 0;

        // Find the index with the densest query word occurrence
        foreach ($queryWords as $word) {
            $pos = mb_stripos($cleanText, $word);
            if ($pos !== false) {
                $bestPos = $pos;
                break;
            }
        }

        $start = max(0, $bestPos - 60);
        $length = 220;
        $excerpt = mb_substr($cleanText, $start, $length);

        if ($start > 0) {
            $excerpt = '...' . $excerpt;
        }
        if (mb_strlen($cleanText) > ($start + $length)) {
            $excerpt .= '...';
        }

        // Highlight matching query words
        $highlightPatterns = array_merge($queryWords, ['সফটওয়্যার', 'সফটওয়্যার', 'ক্যারিয়ার', 'ক্যারিয়ার', 'software', 'developer']);
        foreach ($highlightPatterns as $word) {
            if (mb_strlen($word) >= 2) {
                $excerpt = preg_replace(
                    '/(' . preg_quote($word, '/') . ')/iu',
                    '<mark class="bg-warning-subtle text-dark fw-semibold px-1 rounded">$1</mark>',
                    $excerpt
                );
            }
        }

        return $excerpt;
    }
}
