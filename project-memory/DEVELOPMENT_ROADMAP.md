# AmarSocialContent - Phased Development Roadmap

This roadmap defines the step-by-step, independently testable phases for constructing AmarSocialContent.

---

## Phase 1: Foundation, Multi-Tenancy & Passwordless OTP Authentication
**Objective**: Establish the core Laravel 11/12 and Vue 3 + Bootstrap 5 architecture, complete database migrations for users & OTPs, and implement secure passwordless login.

### Key Deliverables:
- Initialize clean Laravel application with PHP 8.4, MySQL 8.0, and Vite.
- Configure Vue 3 (`<script setup>`), Bootstrap 5, and Bootstrap Icons.
- Database migrations: `users`, `otp_verifications`, `activity_logs`.
- Service: `OtpAuthService` (crypto generation, hashing, rate limiting, expiry).
- Controllers in `App\Http\Controllers\Auth\`:
  - `RegisterController`
  - `LoginOtpController`
  - `VerifyOtpController`
  - `LogoutController`
- Form Requests with validation: `SendOtpRequest`, `VerifyOtpRequest`.
- User and Admin role middleware (`RoleMiddleware`).
- Basic Vue 3 layouts: Public Layout, Auth Shell, and Authenticated Dashboard.
- **Automated Tests**:
  - OTP generation and dispatch.
  - OTP expiration verification after 5 minutes.
  - Rate limiting & cooldown verification.
  - Prevention of brute force attempts (>5 attempts fails and invalidates).
  - Prevention of OTP reuse.

---

## Phase 2: YouTube Aggregation Engine & Dual AI Pipeline
**Objective**: Implement YouTube URL ingestion, transcript extraction, and mentor-style Bengali synthesis using Google Gemini and Groq with automatic fallback.

### Key Deliverables:
- Database migrations: `aggregated_contents`, `content_transcripts`, `content_chunks`, `content_articles`, `ai_processing_logs`.
- Service: `YouTubeTranscriptService`:
  - Regex validation for standard YouTube & Shorts URLs.
  - Metadata retrieval (oEmbed / permitted API: title, channel, thumbnail, duration).
  - Transcript extraction with graceful fallback for manual transcript submission.
- AI Provider Strategy:
  - `AIProviderInterface`
  - `GeminiProvider` (gemini-1.5-flash / gemini-2.5-flash)
  - `GroqProvider` (llama-3.3-70b-versatile fallback)
  - `AIServiceManager` (retry logic, fallback execution, quota error handling)
- Service: `BengaliContentGeneratorService`:
  - Mentor-style Bengali prompt ("আপনি" / "আপনার", retaining English technical terms).
  - Chunking algorithm for long transcripts.
  - Structure builder (Introduction, Main Discussion, Important Concepts, Examples, Takeaways, Key Terms).
- Queue / Background Job: `ProcessYouTubeVideoJob` for asynchronous synthesis.
- Controllers in `App\Http\Controllers\User\`:
  - `AggregatedContentController` (store, show, status, retry).
- Form Requests: `StoreYouTubeContentRequest`, `ManualTranscriptRequest`.
- Vue 3 Components: Ingestion modal/page, live status tracker, transcript editor.
- **Automated Tests**:
  - URL format validation (regular video, Shorts, invalid links).
  - Mocked AI provider success and fallback triggering.
  - Ingestion state transitions (`pending` → `completed`).
  - Strict tenant ownership: User A cannot trigger or view User B's processing job.

---

## Phase 3: Personal Knowledge Library, Notes & Tag Management
**Objective**: Build the library view, article reader, personal notes, bookmarks, and user-scoped tag management.

### Key Deliverables:
- Database migrations: `tags`, `aggregated_content_tag`, `personal_notes`, `bookmarks`, `content_reading_histories`.
- Policies: `AggregatedContentPolicy`, `TagPolicy`, `PersonalNotePolicy`, `BookmarkPolicy`.
- Controllers in `App\Http\Controllers\User\`:
  - `LibraryController` (index with filters, sorting, search).
  - `ArticleDetailController` (show, update title/markdown, delete).
  - `TagController` (CRUD, scoped to user).
  - `PersonalNoteController` (add/update private notes).
  - `BookmarkController` (toggle bookmark).
  - `ReadingHistoryController` (record study time, completed status, weekly learning timeline).
- Controller in `App\Http\Controllers\Public\`:
  - `PublicArticleController` (quarantined public view via `share_token`).
- Vue 3 Components:
  - In-App YouTube Player (16:9 standard & 9:16 Shorts support) embedded directly in reader with play/pause/speed controls.
  - Article reading view with table of contents, reading time, and markdown renderer.
  - Learning Timeline widget (e.g. "Last 7 days: 12 videos studied").
  - Tag pill selector with autocompletion.
  - Personal note drawer.
  - Public article view without authentication.
- **Automated Tests**:
  - IDOR protection: User A cannot read, edit, or delete User B's article or note.
  - Tag isolation: User A's tags are inaccessible to User B.
  - Public sharing test: Public link renders content but excludes user notes and metadata; private link returns 404/403.

---

## Phase 4: LMS Knowledge Organization (Courses, Chapters, Lessons)
**Objective**: Structure knowledge into hierarchical courses, chapters, and lessons linked to aggregated articles.

### Key Deliverables:
- Database migrations: `courses`, `chapters`, `lessons`.
- Policies: `CoursePolicy`, `ChapterPolicy`, `LessonPolicy`.
- Controllers in `App\Http\Controllers\User\`:
  - `CourseController` (CRUD, reordering).
  - `ChapterController` (CRUD, reordering).
  - `LessonController` (CRUD, assign article, reorder).
- Vue 3 Components:
  - Course builder with drag/drop or ordinal reordering.
  - Chapter accordion and lesson list.
  - Lesson viewer displaying attached Bengali article or custom notes.
  - Unassigned article browser (quickly add library items to lessons).
- **Automated Tests**:
  - Multi-tenant security across hierarchy: User A cannot attach User B's article to a course.
  - User A cannot alter User B's course, chapter, or lesson.
  - Reordering and cascade deletions.

---

## Phase 5: Semantic & Hybrid Search Engine
**Objective**: Implement user-scoped natural language search supporting Bengali, English, and mixed terminology using Gemini embeddings and MySQL FULLTEXT.

### Key Deliverables:
- Database migrations: `content_embeddings`, `search_histories`.
- MySQL Fulltext index on `(bengali_title, raw_markdown)`.
- Service: `EmbeddingService`:
  - Generate 768-dim vectors via Gemini `text-embedding-004`.
  - Batch embedding generation for chunks and articles.
- Service: `SemanticSearchService`:
  - Vector cosine similarity calculation on user-scoped records.
  - Lexical full-text score calculation.
  - Hybrid fusion scoring algorithm.
  - Snippet extraction and keyword highlighting.
- Controller in `App\Http\Controllers\User\`:
  - `SearchController` (hybrid, semantic, or full-text with tag/course filters).
- Vue 3 Component:
  - Google-style search interface with query input, filter pills, relevance indicators, and highlighted excerpts.
- **Automated Tests**:
  - Search results are strictly limited to the authenticated user's records.
  - Ranking test: Highly relevant Bengali queries return top matches first.
  - Search history recorded accurately.

---

## Phase 6: Data Migration & Google Takeout YouTube Watch History Import
**Objective**: Create a robust import facility supporting both Google Takeout YouTube watch history uploads (with selective filtering) and migration of existing database records.

### Key Deliverables:
- Database migrations: `import_jobs`, `import_logs`, `watch_history_imports`.
- Service: `GoogleTakeoutWatchHistoryParser`:
  - Parses JSON (`watch-history.json`) and HTML (`watch-history.html`) exports from Google Takeout.
  - Extracts YouTube video ID, title, channel name, and watch timestamp.
  - Interactive selection queue enabling users to cherry-pick educational videos and filter out entertainment noise.
- Service: `YouTubeImportService`:
  - Introspect source database/table schema.
  - Configurable column mapping (URL, Title, ID, Channel, Created At).
  - Duplicate detection against existing video IDs for the user.
  - Batching and transaction-wrapped imports.
- Controller in `App\Http\Controllers\User\`:
  - `ImportController` (inspect, preview, execute, job status).
  - `WatchHistoryImportController` (upload Takeout file, list parsed videos, batch aggregate selected).
- Vue 3 Components:
  - Takeout Upload & Selective Ingestion Wizard (search/filter parsed videos, select-all/deselect, bulk aggregate).
  - Database Import wizard (connection/table select, column mapping, preview table, execution progress).
- **Automated Tests**:
  - Accurate Takeout JSON/HTML parsing.
  - Correct field mapping and persistence.
  - Skipping duplicates without failing the batch.
  - Error logging on malformed rows.

---

## Phase 7: Administration, Health & Activity Monitoring
**Objective**: Provide administrators with platform-wide health monitoring, AI quota/error tracking, and user activity logging without violating user privacy.

### Key Deliverables:
- Controllers in `App\Http\Controllers\Admin\`:
  - `AdminDashboardController` (stats, user counts, aggregation counts, provider usage).
  - `AIHealthController` (provider latency, quota errors, fallback rate).
  - `ActivityLogController` (paginated audit trails).
- Vue 3 Components:
  - Admin dashboard with summary cards and provider status monitors.
- **Automated Tests**:
  - Non-admin users cannot access `/api/admin/*` (403 Forbidden).
  - Admin inspections generate activity log entries.

---

## Phase 8: UI/UX Refinement, Route Preloader & Enterprise Hardening
**Objective**: Ensure cohesive Bootstrap 5 styling, seamless route transitions, comprehensive responsive design, and end-to-end verification.

### Key Deliverables:
- Unified Bootstrap 5 theme with Bootstrap Icons.
- Route transition preloader with timeout fallback and error clearing.
- Form validation standard: inline error messages, `bi-exclamation-circle`, and red borders.
- Mobile navigation bar testing.
- Full automated test suite execution (PHPUnit / Pest).
- Final security and IDOR vulnerability audit.

