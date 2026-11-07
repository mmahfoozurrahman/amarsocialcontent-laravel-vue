# 🌐 AmarSocialContent (আমার সোশ্যাল কনটেন্ট)

> **Turn YouTube videos and Shorts into an organized, searchable Bengali knowledge base — your personal second brain.**

A multi-tenant web platform that converts valuable video content into clean, well-structured Bengali learning articles, lets you organize them into a personal knowledge base, and lets you search your own knowledge by *meaning*.

---

## 🧩 The Problem It Solves

People consume a huge amount of educational content every day — tutorials, podcasts, interviews, YouTube Shorts — but that knowledge is quickly lost:

- **Nothing is remembered** — you watch a video, then forget it, and can never find "that one part".
- **Language barrier** — most technical content is in English, which is slow to absorb and revise for Bengali learners.
- **No structure** — "Watch Later" lists and bookmarks pile up with no real organization.
- **No meaningful search** — you can't find a video again unless you remember the exact title.

**AmarSocialContent** solves this end-to-end:

> Paste a YouTube link → get a clean, mentor-style **Bengali article** (English technical terms kept intact) → organize it into your own **structured second brain** → find it later by **semantic search**.

Every user's knowledge stays **private and fully isolated** from other users.

---

## ✨ Features

### Content & Learning
- 📥 **Add any YouTube video or Short** — automatic metadata and transcript extraction.
- 🧠 **AI Bengali articles** — mentor-style explanations that preserve context, nuance and English technical terms.
- ▶️ **In-app video player** — supports both 16:9 videos and 9:16 Shorts, so you never leave the app.

### Your Second Brain
- 🗂️ **Collections → Sections → Notes** — organize knowledge into your own structured hierarchy (a note can be an aggregated article or your own markdown).
- 🔖 **Bookmarks, personal notes, and custom tags** on every article.
- 🔍 **AI hybrid search** — keyword (lexical) + meaning-based (semantic) search across your saved knowledge, in Bengali, English, or mixed queries.
- ⏱️ **Reading & watch timeline** — study duration, completion status and activity insights.

### Import & Sharing
- 📜 **Google Takeout import** — upload your YouTube watch history and cherry-pick which videos to add.
- 🔗 **Public sharing** — share a single article via a secure link, without exposing any private data.

### Platform
- 🔐 **Passwordless OTP login** — email one-time codes, no passwords.
- 🔑 **Bring Your Own Key (BYOK)** — plug in your own AI credentials (stored encrypted).
- 🧭 **Interactive onboarding tour** for new users.
- 🛡️ **Admin dashboard** with platform metrics, AI health monitoring and activity logs.

### Security
- Strict **multi-tenant isolation** — users can never read, modify or search another user's content.
- AI keys encrypted at rest, safe public share tokens, and rate limiting on auth/AI endpoints.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel (PHP 8.4) — REST API, service-oriented |
| Database | MySQL 8 (SQLite works for a quick local start) |
| Queue | Database queue for background AI processing |
| Frontend | Vue 3 SPA + Vue Router, Bootstrap 5 + Bootstrap Icons |
| Build | Vite |
| AI | Groq (primary) + Google Gemini (fallback / embeddings) |
| Transcripts | Python 3 subtitle engine + manual submission fallback |

---

## 🚀 Installation

### Prerequisites
- **PHP 8.4+** and **Composer**
- **Node.js 18+** and **npm**
- **MySQL 8** (or SQLite for local development)
- **Python 3** (used by the secondary subtitle extraction engine)
- A process runner for the queue worker

### Steps

```bash
# 1. Get the code
git clone https://github.com/mmahfoozurrahman/amarsocialcontent-laravel-vue.git
cd amarsocialcontent-laravel-vue

# 2. Install PHP dependencies
composer install

# 3. Create the environment file and generate the app key
cp .env.example .env
php artisan key:generate

# 4. Configure .env (see the next section), then create the database tables
php artisan migrate

# 5. Install and build the frontend assets
npm install
npm run build
```

### One-command setup (optional)

```bash
composer setup
```
Runs the install, `.env` copy, key generation, migration and asset build in sequence.

### Run the application

```bash
# Web server
php artisan serve        # or point Herd/Valet at the project

# Frontend hot-reload (during development)
npm run dev

# Queue worker — required for AI article generation & imports
php artisan queue:work
```

> Shortcut: `composer run dev` starts the server, queue worker and Vite together.

---

## ⚙️ Environment Configuration

The most important values in `.env`:

```env
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=amarsocialcontent
DB_USERNAME=root
DB_PASSWORD=

# Passwordless OTP
AUTH_DEMO_OTP=true      # true = show the OTP on screen (dev); false = email only (production)
MAIL_MAILER=log         # configure a real SMTP driver in production

# AI providers (users may also bring their own key via BYOK)
AI_PRIMARY_PROVIDER=groq
GROQ_API_KEY=
GEMINI_API_KEY=

# Optional
YOUTUBE_PROXY=          # outbound proxy for YouTube transcript fetching
PYTHON_BINARY=python    # Python binary used by the subtitle engine
```

---

## 🧱 Architecture Overview

A cleanly decoupled **Laravel REST API + Vue 3 single-page app**:

- **Backend (Laravel):** controllers organized by area (`Auth`, `User`, `Admin`) cover authentication, content ingestion, second-brain organization, search, imports and administration. Business logic lives in focused **services** (AI, Auth, Search, YouTube/Takeout). Every user asset is guarded by **policies**, so tenant isolation is enforced consistently.
- **Async processing:** adding a video queues a background job that extracts the transcript and runs the AI synthesis, keeping the UI responsive.
- **Frontend (Vue 3 SPA):** one SPA consumes the REST API, styled with **Bootstrap 5**. Navigation, auth and state use lightweight composables — no heavyweight state library. The feature area "Second Brain" lives under `resources/js/views/user/second-brain/`.
- **Data model:** user-scoped content (articles, tags, collections/sections/notes, personal notes, reading history) on a relational schema, with full-text **and** vector search for retrieval.

---

## 📄 License

Released under the [MIT License](LICENSE).
