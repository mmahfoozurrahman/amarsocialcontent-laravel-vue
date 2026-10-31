<?php

namespace App\Services\Import;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GoogleTakeoutWatchHistoryParser
{
    /**
     * Parse uploaded watch-history file (JSON or HTML).
     *
     * @param string $filePath
     * @param string $extension 'json' or 'html'
     * @return Collection
     */
    public function parse(string $filePath, string $extension): Collection
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $ext = strtolower($extension);

        if ($ext === 'json') {
            return $this->parseJson($filePath);
        }

        if (in_array($ext, ['html', 'htm'])) {
            return $this->parseHtml($filePath);
        }

        throw new \InvalidArgumentException("অসমর্থিত ফাইল ফরম্যাট: {$extension}। শুধুমাত্র .json বা .html ফাইল দিন।");
    }

    /**
     * Parse Google Takeout watch-history.json.
     */
    protected function parseJson(string $filePath): Collection
    {
        $raw = file_get_contents($filePath);
        $items = json_decode($raw, true);

        if (!is_array($items)) {
            throw new \RuntimeException('অবৈধ JSON ফাইল। Google Takeout এর watch-history.json ফাইল দিন।');
        }

        $records = [];
        $seenVideoIds = [];

        foreach ($items as $item) {
            $url = $item['titleUrl'] ?? null;
            if (!$url) {
                continue; // Skipped ads or deleted videos without URL
            }

            $videoId = $this->extractVideoId($url);
            if (!$videoId) {
                continue;
            }

            // Deduplicate multiple watches of the same video within this file (keep latest)
            if (isset($seenVideoIds[$videoId])) {
                continue;
            }

            $rawTitle = $item['title'] ?? 'YouTube Video';
            // Strip Google's prefix "Watched " or "আপনি দেখেছেন "
            $title = preg_replace('/^(Watched\s+|আপনি দেখেছেন\s+)/iu', '', $rawTitle);

            // Channel info
            $channelName = null;
            $channelUrl = null;
            if (!empty($item['subtitles']) && is_array($item['subtitles'])) {
                $sub = $item['subtitles'][0] ?? [];
                $channelName = $sub['name'] ?? null;
                $channelUrl = $sub['url'] ?? null;
            }

            // Watched time
            $watchedAt = null;
            if (!empty($item['time'])) {
                try {
                    $watchedAt = Carbon::parse($item['time']);
                } catch (\Exception $e) {
                    $watchedAt = null;
                }
            }

            $contentType = str_contains($url, '/shorts/') ? 'short' : 'video';

            $records[] = [
                'youtube_video_id' => $videoId,
                'youtube_url' => $url,
                'title' => trim($title),
                'channel_name' => $channelName ? trim($channelName) : 'Unknown Channel',
                'channel_url' => $channelUrl,
                'content_type' => $contentType,
                'watched_at' => $watchedAt ? $watchedAt->toDateTimeString() : null,
            ];

            $seenVideoIds[$videoId] = true;
        }

        return collect($records);
    }

    /**
     * Parse Google Takeout watch-history.html.
     */
    protected function parseHtml(string $filePath): Collection
    {
        $html = file_get_contents($filePath);
        if (empty($html)) {
            return collect([]);
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // Load with UTF-8 encoding
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        // Google Takeout MDL cards
        $cellNodes = $xpath->query('//div[contains(@class, "content-cell")]');

        $records = [];
        $seenVideoIds = [];

        foreach ($cellNodes as $cell) {
            $links = $xpath->query('.//a', $cell);
            if ($links->length === 0) {
                continue;
            }

            // First link is the video link
            $videoAnchor = $links->item(0);
            $url = $videoAnchor->getAttribute('href');
            $rawTitle = $videoAnchor->textContent;

            $videoId = $this->extractVideoId($url);
            if (!$videoId || isset($seenVideoIds[$videoId])) {
                continue;
            }

            $title = preg_replace('/^(Watched\s+|আপনি দেখেছেন\s+)/iu', '', $rawTitle);

            // Channel name from second link if exists
            $channelName = 'Unknown Channel';
            $channelUrl = null;
            if ($links->length > 1) {
                $channelAnchor = $links->item(1);
                $channelName = trim($channelAnchor->textContent);
                $channelUrl = $channelAnchor->getAttribute('href');
            }

            // Timestamp is usually text in the node
            $nodeText = $cell->textContent;
            $watchedAt = null;
            // Match date string like "Oct 25, 2026, 2:30:00 PM UTC" or similar
            if (preg_match('/([A-Za-z]{3}\s+\d{1,2},\s+\d{4},\s+[\d:]+\s*(?:AM|PM)?(?:\s+[A-Z]+)?)/i', $nodeText, $match)) {
                try {
                    $watchedAt = Carbon::parse($match[1]);
                } catch (\Exception $e) {
                    $watchedAt = null;
                }
            }

            $contentType = str_contains($url, '/shorts/') ? 'short' : 'video';

            $records[] = [
                'youtube_video_id' => $videoId,
                'youtube_url' => $url,
                'title' => trim($title),
                'channel_name' => $channelName,
                'channel_url' => $channelUrl,
                'content_type' => $contentType,
                'watched_at' => $watchedAt ? $watchedAt->toDateTimeString() : null,
            ];

            $seenVideoIds[$videoId] = true;
        }

        return collect($records);
    }

    /**
     * Extract 11-char YouTube video ID from URL.
     */
    public function extractVideoId(string $url): ?string
    {
        // 1. Standard watch?v=...
        if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        // 2. Shorts: /shorts/1234567890a
        if (preg_match('/\/shorts\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        // 3. Shortened youtu.be/1234567890a
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        // 4. Embed: /embed/1234567890a
        if (preg_match('/\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

