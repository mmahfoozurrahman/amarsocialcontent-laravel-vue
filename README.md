# 🌐 AmarSocialContent (আমার সোশ্যাল কনটেন্ট)

> **Multi-Tenant Social Knowledge Aggregation & AI-Powered Bengali Learning Platform**  
> Transform YouTube videos and Shorts into structured, high-quality Bengali educational articles, organized into structured LMS courses, searchable via hybrid vector semantic search, with Google Takeout watch history ingestion and Bring-Your-Own-Key (BYOK) privacy.

---

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Groq](https://img.shields.io/badge/Groq-Cloud_AI-F55036?style=for-the-badge)](https://groq.com)
[![Google Gemini](https://img.shields.io/badge/Google-Gemini_AI-4285F4?style=for-the-badge&logo=google&logoColor=white)](https://ai.google.dev)
[![Tests Passing](https://img.shields.io/badge/Tests-52%20Passed%20(100%25)-success?style=for-the-badge)](tests/)

---

## 📖 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
  - [1. Passwordless OTP Authentication](#1-passwordless-otp-authentication)
  - [2. YouTube Aggregation & Transcript Engine](#2-youtube-aggregation--transcript-engine)
  - [3. Dual AI Processing & BYOK Architecture](#3-dual-ai-processing--byok-architecture)
  - [4. Personal Knowledge Library & Reader](#4-personal-knowledge-library--reader)
  - [5. LMS Knowledge Organization](#5-lms-knowledge-organization)
  - [6. AI Semantic & Hybrid Search Engine](#6-ai-semantic--hybrid-search-engine)
  - [7. Google Takeout Watch History Import](#7-google-takeout-watch-history-import)
  - [8. Interactive Onboarding Platform Tour](#8-interactive-onboarding-platform-tour)
  - [9. Enterprise Administration & Health Monitoring](#9-enterprise-administration--health-monitoring)
- [System Architecture & Technology Stack](#-system-architecture--technology-stack)
- [Getting Started & Installation](#-getting-started--installation)
- [Environment Configuration](#-environment-configuration)
- [Running Automated Tests](#-running-automated-tests)
- [Directory Structure](#-directory-structure)
- [Security & Multi-Tenancy Matrix](#-security--multi-tenancy-matrix)
- [License](#-license)

---

## 🌟 Overview

**AmarSocialContent** is an enterprise-grade learning and social knowledge curation application tailored for Bengali speakers, students, and software developers. The platform bridges the gap between fast-paced video consumption and structured textual study by converting English and multilingual video content into actionable, formatted Bengali markdown articles while strictly preserving standard English technical terms (e.g., *API, Dependency Injection, Middleware, Framework*).

Designed with strict zero-trust multi-tenancy, every user operates within their own isolated knowledge repository with encrypted AI credentials, personal notes, custom courses, and reading history.

---

## 🚀 Key Features

### 1. Passwordless OTP Authentication
- **Secure Email Verification**: Fast passwordless login using cryptographically random 6-digit OTPs.
- **Hashed & Expiring Tokens**: OTPs are hashed using SHA-256 with a 5-minute strict TTL and maximum 5 verification attempts.
- **Rate-Limiting & Cooldown**: Built-in 60-second resend cooldown and IP throttling preventing brute-force abuse.
- **Flexible Modes**:
  - `AUTH_DEMO_OTP=true`: Displays test OTP directly on the web interface with one-click copy and auto-fill for local development and QA.
  - `AUTH_DEMO_OTP=false`: Production mode sending actual emails via SMTP/Mailgun with zero credentials leaked in responses.

### 2. YouTube Aggregation & Transcript Engine
- **Universal URL Support**: Works seamlessly with standard YouTube video links (`youtube.com/watch?v=...`), short URLs (`youtu.be/...`), and **YouTube Shorts** (`youtube.com/shorts/...`).
- **Metadata Extraction**: Fetches titles, descriptions, durations, and high-resolution thumbnails via YouTube oEmbed API without requiring a heavy Google Cloud Console API quota.
- **Caption Scraping**: Automatically pulls English and multilingual transcripts from YouTube timedtext caption tracks.
- **Manual Transcript Fallback**: If a video lacks captions, a clean UI modal prompts the user to paste their own transcript or summary.
- **Automatic Channel Tagging**: Ingested content is automatically tagged with the creator's channel name (e.g., `#freeCodeCamp`, `#TraversyMedia`) for instant filtering.

### 3. Dual AI Processing & BYOK Architecture
- **Failover Strategy Pipeline**: Automatically routes prompts to **Groq Cloud** (`openai/gpt-oss-120b` or custom model) for ultra-fast generation, with automatic failover to **Google Gemini** (`gemini-3-flash-preview`) on rate limits or API errors.
- **Bengali Mentor Prompt Engineering**:
  - Educational, motivating mentor tone addressing readers respectfully with "আপনি" and "আপনার".
  - Generates clear sections: Introduction, Key Takeaways, Deep Dive, Code/Syntax Explanations, and Actionable Steps.
  - Enforces English technical terms (e.g., *Database, Queue, Interface*) rather than confusing direct literal translations.
  - Strict anti-markdown formatting on JSON keys to prevent schema parsing errors.
- **Bring Your Own Key (BYOK)**:
  - Users manage their own private Groq and Gemini API keys directly in their **User Profile (`/profile`)**.
  - **AES-256 Encryption at Rest**: Stored using Laravel's encrypted model attributes (`APP_KEY` backed AES-256-CBC).
  - Masked UI representation (`gsk_••••••••12ab` / `AQ.••••••••BNQ`).
  - On-the-fly live connection testing button.
  - Zero cross-tenant key sharing; users only consume their own AI provider quotas.
  - Intelligent fallback: If a user configures only one provider, the system gracefully falls back to the system environment key for secondary failover.

### 4. Personal Knowledge Library & Reader
- **Interactive Library**: Filter and organize articles by tags, content types (Standard Video vs. Shorts), bookmark status, and visibility.
- **Built-in Responsive YouTube Player**: Expandable/collapsible player supporting standard 16:9 widescreen and 9:16 vertical Shorts formats.
- **Live Inline Editor**: Users can modify generated titles and article markdown bodies directly from the reader view.
- **Personal Notes Drawer**: Add private markdown study notes attached to specific articles.
- **Study Timer & Activity Tracking**: Automatically logs seconds spent reading and studying, feeding into platform analytics.
- **Quarantined Public Sharing**: Generate unique, unguessable 32-character tokens (`/public/articles/{token}`) to share articles with friends without exposing private notes, user IDs, or administrative metadata.

### 5. LMS Knowledge Organization
- **Structured Hierarchy**: Organize library articles into structured **Courses ➔ Chapters ➔ Lessons**.
- **Interactive Course Builder**: Create chapters, add lessons, set descriptions, and attach existing aggregated articles.
- **Cross-Tenant Validation**: Strict policy enforcement preventing users from attaching foreign or unowned articles to lessons.
- **Sequential Lesson Reader**: Dedicated reader interface with chapter navigation, previous/next buttons, and completion tracking.

### 6. AI Semantic & Hybrid Search Engine
- **Vector Embeddings**: Generates 768-dimensional dense vector embeddings using Google's `text-embedding-004` (with deterministic subword hash fallback for offline or unconfigured environments).
- **Hybrid Fusion Scoring**: Combines **70% Vector Cosine Similarity** (conceptual semantic match) with **30% MySQL FULLTEXT Lexical Score** (exact keyword match) for superior bilingual discovery.
- **Keyword Highlighting & Excerpts**: Dynamically extracts the most relevant text snippet and wraps query matches in `<mark>` tags.
- **Search History & Filters**: Remembers recent searches with one-click replay and instant search reset.

### 7. Google Takeout Watch History Import
- **Bulk Migration**: Import an entire YouTube watch history by uploading `watch-history.html` or `watch-history.json` exported from Google Takeout.
- **Direct Server File Auto-Detection**: Instant one-click parsing for large history files placed directly in the server public directory (`public/watch-history.json`), avoiding browser upload timeouts or memory constraints.
- **Streaming Parser**: Memory-safe UTF-8 DOM/JSON parser capable of parsing thousands of watch events.
- **Interactive Import Wizard**:
  - Filter items by top YouTube channels via clickable pill badges.
  - Full-text search within imported history.
  - Intelligent deduplication: Detects videos already imported into your library.
  - One-click batch queueing into the background aggregation engine.

### 8. Interactive Onboarding Platform Tour
- **First-Time Guided Journey**: New users receive an engaging, step-by-step interactive onboarding tour modal and backdrop highlight (`useTour.js` + `UserTourGuideModal.vue`).
- **Feature Exploration**:
  - Dashboard overview and quick aggregation actions.
  - Single YouTube / Shorts URL ingestion & manual transcript input.
  - Google Takeout bulk watch history curation.
  - Knowledge Library, custom tagging, and personal study notes.
  - LMS Courses, Chapters, and structured learning paths.
  - AI Profile settings and private API Key setup.
- **On-Demand Access**: Always re-launchable via the "ট্যুর গাইড" (Tour Guide) button in the navigation bar.

### 9. Enterprise Administration & Health Monitoring
- **Admin Control Center (`/admin`)**: Real-time overview of total registered users, completed/pending/failed aggregation jobs, LMS course metrics, and cumulative study hours.
- **Live AI Health Panel**: Real-time status for Groq and Gemini (Operational vs. Degraded), average latency tracking (ms), and prompt/completion token consumption metrics.
- **Audit Trails**: Security activity logging tracking admin views, logins, and key actions.
- **Role Isolation**: Admin dashboard is strictly for monitoring; administrators cannot view private user keys or generate content using other users' accounts.

---

## 🛠 System Architecture & Technology Stack

```
   ┌─────────────────────────────────────────────────────────────┐
   │                        Vue 3 SPA                            │
   │  Bootstrap 5.3 + Bootstrap Icons (Zero Tailwind / Pinia)    │
   │  Composables: useAuth, useContent, useSearch, useProfile...  │
   └──────────────────────────────┬──────────────────────────────┘
                                  │ JSON REST API
   ┌──────────────────────────────▼──────────────────────────────┐
   │                     Laravel 12 API Core                     │
   │   Sanctum Auth · Policies (IDOR Protection) · Rate Limiting │
   └──────┬───────────────────────┬───────────────────────┬──────┘
          │                       │                       │
 ┌────────▼────────┐    ┌─────────▼────────┐    ┌─────────▼────────┐
 │ MySQL 8.0 DB    │    │  Queue Listeners │    │ Strategy AI Hub  │
 │ - AES-256 BYOK  │    │  Background Jobs │    │ - Groq Cloud     │
 │ - 768-Dim Vector│    │  Transcript & AI │    │ - Gemini Flash   │
 │ - FullText Index│    │  Batch Takeout   │    │ - text-embedding │
 └─────────────────┘    └──────────────────┘    └──────────────────┘
```

| Layer | Technology | Key Details |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 12 / PHP 8.4 | Eloquent ORM, Sanctum, Queue Workers |
| **Frontend Framework** | Vue 3 (`<script setup>`) | Composition API, Vue Router 4, Native Reactive State |
| **Styling & UI** | Bootstrap 5.3 + Bootstrap Icons | Enterprise aesthetic, fully responsive, zero Tailwind CSS |
| **Database** | MySQL 8.0 | InnoDB, FULLTEXT indexes, JSON columns, AES-256 encrypted casts |
| **AI Providers** | Groq & Google Gemini | Llama-3.3-70b, gpt-oss-120b, Gemini 1.5/2.5 Flash, text-embedding-004 |
| **Tooling & Build** | Vite 6 | Fast HMR, optimized production builds |

---

## 📦 Getting Started & Installation

### Prerequisites
- **PHP** 8.4 or higher
- **Composer** 2.x
- **Node.js** 20.x or higher & **npm**
- **MySQL** 8.0 or higher

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/amarsocialcontent.git
   cd amarsocialcontent
   ```

2. **Install PHP and Node dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Setup Database:**
   Create a MySQL database named `amarsocialcontent` (or update `.env` with your credentials), then run migrations:
   ```bash
   php artisan migrate
   ```

5. **Start Queue Worker:**
   Because video processing and AI generation run asynchronously, start the queue worker:
   ```bash
   php artisan queue:listen --tries=2
   ```

6. **Compile Frontend & Start Local Server:**
   ```bash
   # In one terminal: run Vite dev server
   npm run dev

   # In another terminal: start Laravel server
   php artisan serve
   ```
   Open `http://127.0.0.1:8000` in your web browser.

---

## ⚙️ Environment Configuration

Add the following keys to your `.env` file to customize the platform:

```dotenv
APP_NAME="AmarSocialContent"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amarsocialcontent
DB_USERNAME=root
DB_PASSWORD=

# Authentication Configuration
# Set true to show OTP on screen for testing; set false for strict email delivery
AUTH_DEMO_OTP=true

# Global AI Fallback Configuration (Optional: Users can supply their own keys in /profile)
GROQ_API_KEY=gsk_your_groq_api_key_here
GROQ_MODEL=openai/gpt-oss-120b

GEMINI_API_KEY=AIzaSy_your_gemini_api_key_here
GEMINI_MODEL=gemini-1.5-flash
GEMINI_EMBEDDING_MODEL=text-embedding-004

AI_PRIMARY_PROVIDER=groq
AI_FALLBACK_PROVIDER=gemini

# Queue Driver
QUEUE_CONNECTION=database
```

---

## 🧪 Running Automated Tests

AmarSocialContent includes a comprehensive automated test suite covering authentication, policies, multi-tenancy, AI strategy failovers, search algorithms, and Takeout parsing:

```bash
# Run all feature and unit tests
php artisan test
```

**Test Suite Coverage Summary:**
- **51 Tests Passed (100%)**
- **193 Assertions**
- Coverage includes:
  - `OtpAuthenticationTest` (Email rate limiting, token expiration, demo flag toggle)
  - `YouTubeAggregationAndAITest` (Shorts & video regex parsing, AI strategy failover)
  - `KnowledgeLibraryAndTagsTest` (Tenant isolation, public share tokens, bookmarks)
  - `LmsKnowledgeOrganizationTest` (Course/Chapter/Lesson hierarchy, cross-tenant protection)
  - `SemanticAndHybridSearchTest` (Vector math, bilingual lexical search, snippet extraction)
  - `GoogleTakeoutWatchHistoryTest` (HTML/JSON Takeout parser, batch import)
  - `AdminHealthAndMonitoringTest` (Metrics aggregation, audit trails, RBAC defense)
  - `UserProfileAndAiSettingsTest` (BYOK encryption, masked keys, connection test)

---

## 📁 Directory Structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # Admin dashboard, AI health, activity logs
│   │   │   ├── Auth/             # OTP login, verify, logout, me
│   │   │   ├── Content/          # Aggregation, library, notes, bookmarks
│   │   │   ├── LMS/              # Courses, chapters, lessons
│   │   │   ├── Search/           # Semantic & hybrid search engine
│   │   │   ├── Takeout/          # Google Takeout watch history import
│   │   │   └── User/             # Profile management & BYOK AI settings
│   │   └── Middleware/           # RoleMiddleware, ThrottleMiddleware
│   ├── Jobs/                     # ProcessYouTubeContentJob
│   ├── Models/                   # User, AggregatedContent, Course, UserAiSetting...
│   ├── Policies/                 # IDOR defense policies for all models
│   └── Services/
│       ├── AI/                   # Strategy pattern (AIServiceManager, Groq, Gemini)
│       ├── Auth/                 # OtpAuthService
│       ├── Search/               # SemanticSearchService, EmbeddingService
│       └── YouTube/              # YouTubeTranscriptService, GoogleTakeoutParser
├── database/
│   └── migrations/               # Normalized schema with encrypted casts & FullText
├── resources/
│   ├── js/
│   │   ├── components/           # Reusable UI (Navbar, VideoPlayer, Modals, Badges)
│   │   ├── composables/          # Vue Composition API modules (useAuth, useProfile...)
│   │   ├── router/               # Vue Router 4 navigation with auth guards
│   │   └── views/                # Auth, Dashboard, Library, LMS, Search, Takeout, Admin
│   └── views/
│       └── app.blade.php         # Single-Page Application root mount
└── tests/
    └── Feature/                  # Complete end-to-end multi-tenant test suite
```

---

## 🛡️ Security & Multi-Tenancy Matrix

1. **Zero-Trust IDOR Protection**: Every database query on user assets (`AggregatedContent`, `Course`, `Lesson`, `PersonalNote`, `UserAiSetting`) explicitly verifies tenant ownership via Laravel Policies (`authorize('view', $content)`).
2. **At-Rest AI Key Encryption**: Sensitive third-party API credentials entered by users are encrypted using AES-256-CBC before storage. Database dumps contain only ciphertext.
3. **Quarantined Public Content**: When an article is shared via a public token, sensitive attributes (internal user IDs, private notes, study times) are omitted from the response.
4. **Rate Limiting**: Authentication and AI generation endpoints are protected against rapid fire or denial-of-service attempts.

---

## 📄 License

This software is developed and distributed under the [MIT License](LICENSE).
