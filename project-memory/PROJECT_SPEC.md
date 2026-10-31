# AmarSocialContent - Project Master Specification

## 1. Project Vision & Purpose

**AmarSocialContent** is a multi-tenant Social Media Knowledge Aggregation Platform and Personal Second Brain designed to transform valuable video content (starting with YouTube Shorts and regular YouTube videos) into an organized, searchable personal knowledge library in Bengali.

People consume vast amounts of educational videos, tutorials, podcasts, and interviews on social media, but that knowledge is quickly lost. AmarSocialContent solves this by converting video content into well-structured, easy-to-read Bengali learning materials that users can organize into courses/lessons, search semantically, revisit, and share.

### Key Pillars
1. **Personal Second Brain**: Organize valuable ideas, bookmarks, personal notes, and lessons.
2. **Social Media Content Aggregator**: Ingest YouTube URLs, extract metadata, captions/transcripts, and process via AI.
3. **AI-Powered Bengali Mentor**: Generate high-fidelity, mentor-style Bengali explanations preserving terminology, context, and depth (no superficial 1-line summaries).
4. **Personal LMS**: Hierarchy of Workspace → Course → Chapter → Lesson.
5. **Semantic Knowledge Retrieval**: Retrieve stored knowledge through user-scoped vector & full-text hybrid search (supporting Bengali, English, and mixed tech queries).
6. **Multi-Tenant Security**: Total isolation between user libraries, robust IDOR prevention, and safe public sharing.

---

## 2. Technology Stack

### Backend
- **Framework**: Laravel 11 / 12 (running on PHP 8.4.25).
- **Database**: MySQL 8.0.
- **Cache & Queue**: Redis (high-performance caching for 1000+ concurrent users, queue workers for AI synthesis).
- **Architecture**: REST API architecture with namespaced controllers (`App\Http\Controllers\Admin\`, `User\`, `Public\`, `Auth\`).
- **Authentication**: Passwordless OTP authentication via email (hashed OTP, rate-limited, expiration).
- **Authorization**: Laravel Policies, Gates, Form Requests, and API Resources.

### Frontend
- **Framework**: Vue 3 with Composition API and `<script setup>`.
- **State Management**: Native Vue 3 reactivity (`ref`, `reactive`, `computed`) and composables (Strictly NO Pinia, NO Tailwind).
- **Styling**: Bootstrap 5 + Bootstrap Icons.
- **Embedded Media**: In-App YouTube Player (16:9 standard and 9:16 Shorts support) with native playback controls directly inside the article reader.
- **Build Tool**: Vite.

### AI Integration
- **Google Gemini API**: Text generation (`gemini-1.5-flash`, `gemini-2.5-flash`) & embeddings (`text-embedding-004`).
- **Groq API**: High-speed fallback text generation (`llama-3.3-70b-versatile`, `mixtral-8x7b-32768`).
- **Resilience**: Dynamic fallback, rate-limit backoff, token usage logging, and configurable credentials via `.env`.

---

## 3. Core Functional Modules

### Module 1: YouTube Content Aggregation
- **Input**: YouTube regular videos and YouTube Shorts.
- **Extraction**: Video ID, title, channel, thumbnail, duration, publication date via oEmbed and permitted APIs.
- **Transcripts**: Extraction via permitted methods; manual transcript submission fallback.
- **AI Processing**:
  - Mentor-style Bengali explanation addressing the user with "আপনি" and "আপনার".
  - Retain English technical terms and core analogies.
  - Chunk-based processing for long videos without information loss.
  - Preserve context, opposing viewpoints, and actionable insights.

### Module 2: Personal Knowledge Library & Reading Timeline
- **Library Features**: View all aggregated items, filter by tags/courses/visibility, edit titles/markdown content, soft-delete, re-process with alternative AI provider.
- **In-App Media Player**: Built-in YouTube player with full playback controls (play, pause, speed, seek) supporting both 16:9 standard videos and 9:16 Shorts so users never need to leave the platform.
- **Platform Reading & Watch Timeline**: Track reading duration, view counts, and completion status (`content_reading_histories`). Provides user learning analytics (e.g., *"In the last 7 days you studied 12 technical videos"*).
- **Visibility**: Default **Private** (owner only). **Public** creates a secure shareable link without exposing user data or private notes.
- **User Annotations**: Personal notes and bookmarking per article.

### Module 3: LMS / Knowledge Organization
- **Hierarchy**: Course → Chapter → Lesson.
- **Course**: Title, description, cover image, visibility, sort order.
- **Chapter**: Ordered groupings within a course.
- **Lesson**: Can directly reference an aggregated article or host custom markdown learning material.
- **Organization**: Drag/drop or ordinal reordering, unassigned article browser.

### Module 4: Tag Management System
- User-scoped tags (tags belong to a user, avoiding cross-tenant leakage).
- Many-to-many relationship with aggregated articles.
- Tag filtering, search, and duplicate tag prevention per user.

### Module 5: Semantic Search & AI Retrieval
- User-scoped search across saved articles only.
- Handles natural language queries in Bengali, English, and mixed Bangla-English.
- Hybrid search engine:
  - Lexical / Full-Text Search via MySQL FULLTEXT index.
  - Semantic Vector Similarity Search via embeddings (Gemini `text-embedding-004`) with cosine similarity scoring.
- Google-style result interface with highlighted snippets, relevance badges, tags, and course references.

### Module 6: Data Migration & Google Takeout YouTube Watch History Import
- **Google Takeout Watch History Import**:
  - Upload `watch-history.json` or `watch-history.html` exported from Google Takeout.
  - Parse watched videos instantly with date, video ID, channel, and title.
  - Interactive selection UI allowing users to cherry-pick educational videos and discard entertainment noise.
  - Bulk queue aggregation into personal knowledge library.
- **Legacy Database Migration**:
  - Configurable import service to migrate existing YouTube database records.
  - Source database/table inspection, column-to-field mapping.
  - Duplicate video ID detection and conflict avoidance.
  - Preview, batch execution, error logging, and import history.

### Module 7: Multi-Tenancy & Security
- Strict data isolation: User A cannot read, update, delete, or search User B's content.
- IDOR defense via route model binding policies and explicit `user_id` query constraints.
- Admin dashboard for platform metrics and activity logs without unauthorized exposure of private user notes.

### Module 8: Passwordless OTP Authentication
- Clean email-based login/registration.
- Cryptographically secure OTP generation, hashed storage, 5-minute expiration, max 5 verification attempts.

