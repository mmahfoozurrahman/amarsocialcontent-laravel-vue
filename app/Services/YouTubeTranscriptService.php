<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Throwable;

class YouTubeTranscriptService
{
    /**
     * InnerTube player clients, tried in order until caption tracks are found.
     *
     * ANDROID_VR is listed first because it is the profile that still serves
     * timedtext URLs returning content without a proof-of-origin (pot) token;
     * the WEB profiles are kept as a secondary attempt.
     *
     * @var array<string, array{context: array<string, mixed>, headers: array<string, string>}>
     */
    protected const INNERTUBE_CLIENTS = [
        'android_vr' => [
            'context' => [
                'client' => [
                    'clientName' => 'ANDROID_VR',
                    'clientVersion' => '1.60.19',
                    'deviceMake' => 'Oculus',
                    'deviceModel' => 'Quest 3',
                    'androidSdkVersion' => 32,
                    'osName' => 'Android',
                    'osVersion' => '12',
                    'hl' => 'en',
                    'gl' => 'US',
                ],
            ],
            'headers' => [],
        ],
        'web' => [
            'context' => [
                'client' => [
                    'clientName' => 'WEB',
                    'clientVersion' => '2.20240304.00.00',
                    'hl' => 'en',
                    'gl' => 'US',
                ],
            ],
            'headers' => [
                'X-Youtube-Client-Name' => '1',
                'X-Youtube-Client-Version' => '2.20240304.00.00',
            ],
        ],
        'web_embedded' => [
            'context' => [
                'client' => [
                    'clientName' => 'WEB_EMBEDDED_PLAYER',
                    'clientVersion' => '1.20240303.00.00',
                    'hl' => 'en',
                    'gl' => 'US',
                ],
            ],
            'headers' => [
                'X-Youtube-Client-Name' => '56',
                'X-Youtube-Client-Version' => '1.20240303.00.00',
            ],
        ],
    ];

    protected const INNERTUBE_ENDPOINT = 'https://www.youtube.com/youtubei/v1/player';

    protected const DESKTOP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

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
            $response = $this->http()->timeout(6)->get('https://www.youtube.com/oembed', [
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
        } catch (Throwable $e) {
            \Log::warning('YouTube oEmbed fetch failed: '.$e->getMessage(), ['video_id' => $videoId]);
        }

        return [
            'title' => 'ইউটিউব ভিডিও ('.$videoId.')',
            'channel_name' => 'YouTube Creator',
            'thumbnail_url' => $thumbnailFallback,
            'content_type' => $contentType,
        ];
    }

    /**
     * Extract a transcript by walking a resilient fallback chain: YouTube's
     * InnerTube player API across several client profiles, then the public
     * watch page scrape. Returns null only when every source is exhausted.
     *
     * @return array{full_transcript: string, language: string, is_manual: bool, source: string}|null
     */
    public function extractTranscript(string $videoId, ?string $translateTo = null): ?array
    {
        foreach ($this->transcriptSources() as $source => $fetcher) {
            try {
                $result = $fetcher($videoId, $translateTo);
            } catch (Throwable $e) {
                Log::info("Transcript source '{$source}' threw an exception: ".$e->getMessage(), [
                    'video_id' => $videoId,
                ]);

                continue;
            }

            if ($result !== null && ($result['full_transcript'] ?? '') !== '') {
                Log::info('Transcript extracted successfully.', [
                    'video_id' => $videoId,
                    'source' => $source,
                    'language' => $result['language'],
                ]);

                return $result + ['source' => $source];
            }
        }

        Log::warning('All transcript sources were exhausted.', ['video_id' => $videoId]);

        return null;
    }

    /**
     * Ordered transcript strategies, from cheapest to most resilient.
     *
     * @return array<string, callable(string, ?string): (array{full_transcript: string, language: string, is_manual: bool}|null)>
     */
    protected function transcriptSources(): array
    {
        $sources = [];

        foreach (array_keys(self::INNERTUBE_CLIENTS) as $client) {
            $sources["innertube_{$client}"] = fn (string $videoId, ?string $translateTo): ?array => $this->fetchViaInnerTube($videoId, $translateTo, $client);
        }

        // Legacy scrape kept last: many regions now serve empty timedtext bodies
        // from the watch page, but it still works where the pot check is absent.
        $sources['watch_page'] = fn (string $videoId, ?string $translateTo): ?array => $this->fetchViaWatchPage($videoId, $translateTo);

        // Secondary Subtitle Engine: Extracts captions/transcripts bypassing PO-token blocks
        $sources['secondary_subtitle_engine'] = fn (string $videoId, ?string $translateTo): ?array => $this->fetchViaSecondaryEngine($videoId, $translateTo);

        return $sources;
    }

    /**
     * Scrape caption tracks out of the public watch page's ytInitialPlayerResponse.
     */
    protected function fetchViaWatchPage(string $videoId, ?string $translateTo = null): ?array
    {
        $response = $this->http()
            ->timeout(8)
            ->withHeaders(['Accept-Language' => 'en-US,en;q=0.9,bn;q=0.8'])
            ->get("https://www.youtube.com/watch?v={$videoId}");

        if (! $response->successful()) {
            return null;
        }

        if (! preg_match('/"captionTracks":\s*(\[.*?\])/', $response->body(), $matches)) {
            return null;
        }

        $tracks = json_decode($matches[1], true);

        if (! is_array($tracks) || $tracks === []) {
            return null;
        }

        $track = $this->selectCaptionTrack($tracks);

        if ($track === null) {
            return null;
        }

        return $this->buildTranscriptPayload($track, $translateTo);
    }

    /**
     * Query YouTube's InnerTube player API using a specific client profile.
     */
    protected function fetchViaInnerTube(string $videoId, ?string $translateTo, string $clientKey): ?array
    {
        $client = self::INNERTUBE_CLIENTS[$clientKey] ?? null;

        if ($client === null) {
            return null;
        }

        $response = $this->http()
            ->timeout(8)
            ->withHeaders(array_merge(['Content-Type' => 'application/json'], $client['headers']))
            ->post(self::INNERTUBE_ENDPOINT, [
                'context' => $client['context'],
                'videoId' => $videoId,
                'contentCheckOk' => true,
                'racyCheckOk' => true,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $tracks = $response->json('captions.playerCaptionsTracklistRenderer.captionTracks');

        if (! is_array($tracks) || $tracks === []) {
            return null;
        }

        $track = $this->selectCaptionTrack($tracks);

        if ($track === null) {
            return null;
        }

        return $this->buildTranscriptPayload($track, $translateTo);
    }

    /**
     * Download the chosen caption track and shape the transcript payload.
     *
     * @param  array<string, mixed>  $track
     * @return array{full_transcript: string, language: string, is_manual: bool}|null
     */
    protected function buildTranscriptPayload(array $track, ?string $translateTo = null): ?array
    {
        $text = $this->downloadCaption($track, $translateTo);

        if ($text === null) {
            return null;
        }

        return [
            'full_transcript' => $text,
            'language' => $track['languageCode'] ?? 'en',
            'is_manual' => ($track['kind'] ?? '') !== 'asr',
        ];
    }

    /**
     * Pick the most useful track: manual English/Bengali first, then any manual
     * track, then an auto-generated one, and finally whatever is available.
     *
     * @param  array<int, array<string, mixed>>  $tracks
     * @return array<string, mixed>|null
     */
    protected function selectCaptionTrack(array $tracks): ?array
    {
        $usable = array_values(array_filter(
            $tracks,
            fn ($track): bool => is_array($track) && ! empty($track['baseUrl'])
        ));

        if ($usable === []) {
            return null;
        }

        $score = function (array $track): int {
            $score = ($track['kind'] ?? '') === 'asr' ? 0 : 100;

            if (in_array(strtolower($track['languageCode'] ?? ''), ['en', 'bn'], true)) {
                $score += 50;
            }

            return $score;
        };

        usort($usable, fn (array $a, array $b): int => $score($b) <=> $score($a));

        return $usable[0];
    }

    /**
     * Download a caption track, trying the json3 request first and then the
     * plain timedtext request, parsing whichever payload YouTube returns.
     *
     * @param  array<string, mixed>  $track
     */
    protected function downloadCaption(array $track, ?string $translateTo = null): ?string
    {
        $baseUrl = $track['baseUrl'] ?? null;

        if (empty($baseUrl)) {
            return null;
        }

        foreach (['json3', null] as $format) {
            $params = [];

            if ($format !== null) {
                $params['fmt'] = $format;
            }

            if (! empty($translateTo)) {
                $params['tlang'] = $translateTo;
            }

            $response = $this->http()->timeout(8)->get($this->withQuery($baseUrl, $params));

            if (! $response->successful()) {
                continue;
            }

            $text = $this->parseCaptionBody($response->body());

            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    /**
     * Parse a timedtext payload, detecting json3 vs XML from the payload itself
     * because YouTube may ignore the requested format.
     */
    protected function parseCaptionBody(string $body): string
    {
        $trimmed = ltrim($body);

        if ($trimmed === '') {
            return '';
        }

        return str_starts_with($trimmed, '{')
            ? $this->parseJson3($body)
            : $this->parseTimedTextXml($body);
    }

    /**
     * Merge query parameters into a URL while preserving the existing signature
     * and expiry tokens exactly as YouTube issued them.
     *
     * @param  array<string, string>  $params
     */
    protected function withQuery(string $url, array $params): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $query);

        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }

        $rebuilt = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        if (isset($parts['port'])) {
            $rebuilt .= ':'.$parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '';

        return $rebuilt.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Parse a json3 timedtext payload into clean readable text.
     */
    protected function parseJson3(string $jsonContent): string
    {
        // Strip YouTube's anti-JSON-hijacking prefix when present.
        $jsonContent = preg_replace('/^\)\]\}\'/', '', ltrim($jsonContent));

        $data = json_decode($jsonContent, true);

        if (! is_array($data) || empty($data['events'])) {
            return '';
        }

        $lines = [];

        foreach ($data['events'] as $event) {
            if (empty($event['segs'])) {
                continue;
            }

            $chunk = '';

            foreach ($event['segs'] as $segment) {
                $text = $segment['utf8'] ?? '';

                // Rolling auto-caption events append a bare newline segment.
                if ($text === "\n") {
                    continue;
                }

                $chunk .= $text;
            }

            $chunk = trim(preg_replace('/\s+/u', ' ', $chunk));

            if ($chunk !== '') {
                $lines[] = $chunk;
            }
        }

        return implode(' ', $lines);
    }

    /**
     * Build an HTTP client, applying the optional outbound proxy when configured.
     */
    protected function http(): PendingRequest
    {
        $request = Http::withHeaders([
            'User-Agent' => self::DESKTOP_USER_AGENT,
        ]);

        $proxy = config('services.youtube.proxy');

        if (! empty($proxy)) {
            $request = $request->withOptions(['proxy' => $proxy]);
        }

        return $request;
    }

    /**
     * Parse XML timedtext into clean readable text.
     */
    protected function parseTimedTextXml(string $xmlContent): string
    {
        $lines = [];
        $xml = @simplexml_load_string($xmlContent);

        if ($xml) {
            // Legacy formats expose <text> nodes; format 3 nests <p> inside <body>.
            $nodes = $xml->text;

            if (count($nodes) === 0 && isset($xml->body)) {
                $nodes = $xml->body->p;
            }

            foreach ($nodes as $node) {
                $line = html_entity_decode((string) $node, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $line = trim(preg_replace('/\s+/', ' ', $line));
                if (! empty($line)) {
                    $lines[] = $line;
                }
            }
        } else {
            // Regex fallback if simplexml fails
            if (preg_match_all('/<(?:text|p)[^>]*>(.*?)<\/(?:text|p)>/is', $xmlContent, $matches)) {
                foreach ($matches[1] as $raw) {
                    $cleaned = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));
                    if (! empty($cleaned)) {
                        $lines[] = $cleaned;
                    }
                }
            }
        }

        return implode(' ', $lines);
    }

    /**
     * Secondary Subtitle Extraction Engine.
     * Uses the standalone Python engine (with youtube-transcript-api)
     * to bypass YouTube's proof-of-origin (PO-token) bot barriers.
     *
     * @return array{full_transcript: string, language: string, is_manual: bool, source: string}|null
     */
    protected function fetchViaSecondaryEngine(string $videoId, ?string $translateTo = null): ?array
    {
        $scriptPath = base_path('app/Services/YouTube/scripts/extract_subtitles.py');
        if (! file_exists($scriptPath)) {
            return null;
        }

        $pythonBin = config('services.youtube.python_binary', env('PYTHON_BINARY', 'python'));
        $preferredLangs = $translateTo ? "{$translateTo},en,bn,hi" : 'en,en-US,en-GB,bn,hi';
        $proxy = config('services.youtube.proxy', '');

        $cmd = [$pythonBin, $scriptPath, $videoId, $preferredLangs];
        if (! empty($proxy)) {
            $cmd[] = $proxy;
        }

        try {
            $process = new Process($cmd);
            $process->setTimeout(18);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::warning('Secondary subtitle engine process failed: '.$process->getErrorOutput(), [
                    'video_id' => $videoId,
                ]);

                return null;
            }

            $output = trim($process->getOutput());
            $data = json_decode($output, true);

            if (is_array($data) && ! empty($data['success']) && ! empty($data['full_transcript'])) {
                return [
                    'full_transcript' => $data['full_transcript'],
                    'language' => $data['language'] ?? 'en',
                    'is_manual' => (bool) ($data['is_manual'] ?? false),
                    'source' => $data['source'] ?? 'secondary_subtitle_engine',
                ];
            }

            if (! empty($data['error'])) {
                Log::info('Secondary subtitle engine reported: '.$data['error'], [
                    'video_id' => $videoId,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Secondary subtitle engine exception: '.$e->getMessage(), [
                'video_id' => $videoId,
            ]);
        }

        return null;
    }
}
