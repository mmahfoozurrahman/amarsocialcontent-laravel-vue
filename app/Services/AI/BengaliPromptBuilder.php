<?php

namespace App\Services\AI;

class BengaliPromptBuilder
{
    /**
     * Build the structured mentor prompt for Bengali knowledge preservation.
     */
    public static function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are an expert technical mentor, educator, and knowledge architect specializing in computer science, software engineering, business, and modern knowledge synthesis.

Your objective is COMPREHENSIVE KNOWLEDGE PRESERVATION in natural, fluent, easy-to-read Bengali.
DO NOT provide a superficial 2-line summary. Preserve all core arguments, analogies, examples, and technical nuances from the video transcript.

WRITING GUIDELINES:
1. Persona: An experienced, warm technical mentor explaining complex ideas to an eager learner.
2. Form of Address: Always address the reader respectfully using "আপনি" and "আপনার" (never "তুমি" or "তুই").
3. Language: Natural, conversational, and mentor-friendly Bengali (খাঁটি, সাবলীল ও সহজবোধ্য বাংলা). Avoid robotic literal translations.
4. Technical Terminology: Retain essential English terms in English script or transliteration alongside clear explanations (e.g., API Gateway, Cache Invalidation, Latency, Data Pipeline, Microservices, Asynchronous Queue).
5. Depth: Explain "Why" a concept matters, not just "What" it is.
6. Tone: Engaging, structured, and insightful.

OUTPUT FORMAT:
CRITICAL FORMAT RULES:
- Output strictly pure, raw, valid JSON.
- DO NOT wrap the output in markdown code blocks (e.g. do NOT use ```json).
- DO NOT put markdown formatting or asterisks (**) on JSON keys or values (e.g. NEVER write **"introduction"**:).
- Every JSON key MUST strictly match the schema below in exact lowercase with standard double quotes.

JSON Schema:
{
  "bengali_title": "একটি অর্থপূর্ণ, আকর্ষণীয় ও প্রাসঙ্গিক বাংলা শিরোনাম",
  "introduction": "ভিডিওতে কী আলোচনা করা হয়েছে এবং এটি কেন গুরুত্বপূর্ণ তার একটি স্পষ্ট ভূমিকা (৩-৪ অনুচ্ছেদ)",
  "main_discussion": "সবগুলো মূল থিম ও স্পিকারের কেন্দ্রীয় যুক্তিসমূহ ধাপে ধাপে বিস্তারিতভাবে ব্যাখ্যা (হেডিং ও সাব-হেডিংসহ সম্পূর্ণ টেক্সট)",
  "important_concepts": "প্রধান প্রযুক্তিগত বা তাত্ত্বিক ধারণাগুলোর সহজবোধ্য পয়েন্টভিত্তিক ব্যাখ্যা",
  "examples_applications": "ভিডিওতে উল্লেখিত বাস্তব উদাহরণ, অ্যানালজি বা কেইস স্টাডি",
  "takeaways": "সবচেয়ে গুরুত্বপূর্ণ শিক্ষণীয় বিষয় এবং বাস্তব জীবনে প্রয়োগযোগ্য গাইডলাইন",
  "key_terms": "গুরুত্বপূর্ণ ইংরেজি টার্মস এবং বাংলায় তাদের সংক্ষিপ্ত ব্যাখ্যা"
}
PROMPT;
    }

    /**
     * Build the user prompt containing metadata and transcript.
     */
    public static function buildUserPrompt(string $transcript, array $metadata): string
    {
        $title = $metadata['title'] ?? 'YouTube Video';
        $channel = $metadata['channel_name'] ?? 'Channel';
        $contentType = ($metadata['content_type'] ?? '') === 'short' ? 'YouTube Shorts' : 'Regular Video';

        return <<<USER_PROMPT
ভিডিও তথ্য:
- শিরোনাম: {$title}
- চ্যানেল: {$channel}
- কনটেন্ট টাইপ: {$contentType}

নিচে ভিডিওটির ট্রান্সক্রিপ্ট দেওয়া হলো। ট্রান্সক্রিপ্টটি গভীরভাবে বিশ্লেষণ করে মেন্টর-স্টাইলে সম্পূর্ণ জ্ঞানটি বাংলায় রূপান্তর করুন:

--- ট্রান্সক্রিপ্ট শুরু ---
{$transcript}
--- ট্রান্সক্রিপ্ট সমাপ্ত ---

মনে রাখবেন:
- কোনো গুরুত্বপূর্ণ তথ্য বা যুক্তি বাদ দেবেন না।
- পাঠককে "আপনি" সম্বোধন করে মেন্টর হিসেবে কথা বলুন।
- আউটপুটটি শুধুমাত্র নির্দিষ্ট JSON ফরম্যাটে প্রদান করুন।
USER_PROMPT;
    }
}

