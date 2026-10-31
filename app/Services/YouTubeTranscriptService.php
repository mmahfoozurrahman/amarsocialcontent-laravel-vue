<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class YouTubeTranscriptService
{
    /**
     * Parse and validate a YouTube URL, extracting video ID and format type.
     */
    public function parseYouTubeUrl(string $url): array
    {
        $trimmed = trim($url);

        // Pattern for YouTube Shorts: youtube.com/shorts/{id}
        if (preg_match('/(?:youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/i', $trimmed, $matches)) {
            return [
                'video_id' => $matches[1],
                'content_type' => 'short',
                'clean_url' => "https://www.youtube.com/shorts/{$matches[1]}",
            ];
        }

        // Pattern for regular YouTube videos: watch?v={id}, youtu.be/{id}, etc.
        $patterns = [
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $trimmed, $matches)) {
                return [
                    'video_id' => $matches[1],
                    'content_type' => 'video',
                    'clean_url' => "https://www.youtube.com/watch?v={$matches[1]}",
                ];
            }
        }

        throw ValidationException::withMessages([
            'youtube_url' => ['একটি সঠিক ইউটিউব ভিডিও বা শর্টসের লিঙ্ক প্রদান করুন।'],
        ]);
    }

    /**
     * Fetch video metadata via YouTube oEmbed API.
     */
    public function fetchMetadata(string $videoId, string $contentType = 'video'): array
    {
        $canonicalUrl = "https://www.youtube.com/watch?v={$videoId}";
        $thumbnailFallback = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";

        try {
            $response = Http::timeout(6)->get('https://www.youtube.com/oembed', [
                'url' => $canonicalUrl,
                'format' => 'json',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'title' => $data['title'] ?? 'ইউটিউব ভিডিও',
                    'channel_name' => $data['author_name'] ?? 'অজানা চ্যানেল',
                    'thumbnail_url' => $data['thumbnail_url'] ?? $thumbnailFallback,
                    'content_type' => $contentType,
                ];
            }
        } catch (\Throwable $e) {
            \Log::warning('YouTube oEmbed fetch failed: ' . $e->getMessage(), ['video_id' => $videoId]);
        }

        return [
            'title' => 'ইউটিউব ভিডিও (' . $videoId . ')',
            'channel_name' => 'YouTube Creator',
            'thumbnail_url' => $thumbnailFallback,
            'content_type' => $contentType,
        ];
    }

    /**
     * Extract available transcripts via public timedtext or subtitle tracks.
     * Returns null if no automatic captions can be extracted.
     */
    public function extractTranscript(string $videoId): ?array
    {
        try {
            // Attempt to fetch public timedtext metadata from the video watch page
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept-Language' => 'en-US,en;q=0.9,bn;q=0.8',
                ])
                ->get("https://www.youtube.com/watch?v={$videoId}");

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            // Extract captionTracks from ytInitialPlayerResponse
            if (preg_match('/"captionTracks":\s*(\[.*?\])/', $html, $matches)) {
                $tracks = json_decode($matches[1], true);

                if (!empty($tracks) && is_array($tracks)) {
                    // Prioritize English or Bengali track, otherwise take the first available
                    $selectedTrack = null;
                    foreach ($tracks as $track) {
                        $lang = strtolower($track['languageCode'] ?? '');
                        if ($lang === 'en' || $lang === 'bn') {
                            $selectedTrack = $track;
                            break;
                        }
                    }

                    if (!$selectedTrack) {
                        $selectedTrack = $tracks[0];
                    }

                    if (!empty($selectedTrack['baseUrl'])) {
                        // Request the XML caption data
                        $captionResponse = Http::timeout(6)->get($selectedTrack['baseUrl']);
                        if ($captionResponse->successful()) {
                            $captionXml = $captionResponse->body();
                            $parsedText = $this->parseTimedTextXml($captionXml);

                            if (!empty($parsedText)) {
                                return [
                                    'full_transcript' => $parsedText,
                                    'language' => $selectedTrack['languageCode'] ?? 'en',
                                    'is_manual' => false,
                                ];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::info('Automated transcript extraction not available: ' . $e->getMessage(), ['video_id' => $videoId]);
        }

        return null;
    }

    /**
     * Parse XML timedtext into clean readable text.
     */
    protected function parseTimedTextXml(string $xmlContent): string
    {
        $lines = [];
        $xml = @simplexml_load_string($xmlContent);

        if ($xml) {
            foreach ($xml->text as $textNode) {
                $line = html_entity_decode((string) $textNode, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $line = trim(preg_replace('/\s+/', ' ', $line));
                if (!empty($line)) {
                    $lines[] = $line;
                }
            }
        } else {
            // Regex fallback if simplexml fails
            if (preg_match_all('/<text[^>]*>(.*?)<\/text>/is', $xmlContent, $matches)) {
                foreach ($matches[1] as $raw) {
                    $cleaned = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));
                    if (!empty($cleaned)) {
                        $lines[] = $cleaned;
                    }
                }
            }
        }

        return implode(' ', $lines);
    }
}

