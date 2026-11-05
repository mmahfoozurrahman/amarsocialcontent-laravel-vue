#!/usr/bin/env python3
"""
AmarSocialContent - Secondary Subtitle & Transcript Extraction Engine
Extracts captions/transcripts from YouTube videos using direct timedtext and
InnerTube protocol emulation, bypassing PO-token blocks.
"""

import sys
import json

def extract_transcript(video_id, preferred_langs=None, proxy_url=None):
    if preferred_langs is None:
        preferred_langs = ['en', 'en-US', 'en-GB', 'bn', 'hi']
        
    try:
        from youtube_transcript_api import YouTubeTranscriptApi
        from youtube_transcript_api.proxies import GenericProxyConfig
    except ImportError:
        return {
            "success": False,
            "video_id": video_id,
            "error": "youtube-transcript-api is not installed. Please run: pip install youtube-transcript-api"
        }

    try:
        proxy_config = None
        if proxy_url:
            proxy_config = GenericProxyConfig(http_url=proxy_url, https_url=proxy_url)

        api = YouTubeTranscriptApi(proxy_config=proxy_config)
        transcript_list = api.list(video_id)
        
        selected_transcript = None
        
        # Priority 1: Check for preferred languages among manual transcripts
        for lang in preferred_langs:
            for t in transcript_list:
                if not t.is_generated and t.language_code.lower() == lang.lower():
                    selected_transcript = t
                    break
            if selected_transcript:
                break
                
        # Priority 2: Check for preferred languages among auto-generated transcripts
        if not selected_transcript:
            for lang in preferred_langs:
                for t in transcript_list:
                    if t.is_generated and t.language_code.lower() == lang.lower():
                        selected_transcript = t
                        break
                if selected_transcript:
                    break
                    
        # Priority 3: Any manual transcript
        if not selected_transcript:
            for t in transcript_list:
                if not t.is_generated:
                    selected_transcript = t
                    break
                    
        # Priority 4: Fall back to first available transcript
        if not selected_transcript:
            all_transcripts = list(transcript_list)
            if all_transcripts:
                selected_transcript = all_transcripts[0]

        if not selected_transcript:
            return {
                "success": False,
                "video_id": video_id,
                "error": "No subtitles or transcripts found for this video."
            }

        fetched = selected_transcript.fetch()
        
        # Build clean plain text
        lines = []
        for snippet in fetched.snippets:
            txt = snippet.text.strip()
            if txt and txt != "\n":
                lines.append(txt)
                
        full_text = " ".join(lines)
        # Normalize whitespace
        full_text = " ".join(full_text.split())

        return {
            "success": True,
            "video_id": video_id,
            "full_transcript": full_text,
            "language": selected_transcript.language_code,
            "is_manual": not selected_transcript.is_generated,
            "source": "secondary_subtitle_engine"
        }

    except Exception as e:
        return {
            "success": False,
            "video_id": video_id,
            "error": str(e)
        }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "No video ID provided."}))
        sys.exit(1)
        
    vid = sys.argv[1].strip()
    langs = sys.argv[2].split(",") if len(sys.argv) > 2 and sys.argv[2] else None
    proxy = sys.argv[3].strip() if len(sys.argv) > 3 and sys.argv[3] else None
    
    result = extract_transcript(vid, langs, proxy)
    print(json.dumps(result, ensure_ascii=False))
