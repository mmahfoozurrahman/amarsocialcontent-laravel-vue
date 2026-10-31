# AmarSocialContent - Security & Multi-Tenancy Architecture

## 1. Multi-Tenancy & Data Isolation Model

AmarSocialContent adopts a **Shared Database, Logical Tenant Separation** model. In this model, security and strict data isolation are enforced across multiple architectural layers:

```
[ HTTP Request ]
       ↓
[ Route Middleware (Auth, Role, Rate Limiter) ]
       ↓
[ Form Request (Input Validation & Sanitization) ]
       ↓
[ Policy Check (Authorization & IDOR Defense) ]
       ↓
[ Scoped Eloquent Query (Tenant Isolation) ]
       ↓
[ API Resource (Data Masking & Privacy Filter) ]
```

### 1.1 Insecure Direct Object Reference (IDOR) Prevention Matrix

Every tenant-owned entity is guarded by dedicated Laravel Policies and server-side ownership checks:

| Entity | Route Pattern | Policy Class | Server-Side Verification Rule |
|---|---|---|---|
| **AggregatedContent** | `/api/user/articles/{id}` | `AggregatedContentPolicy` | `$user->id === $content->user_id` |
| **PersonalNote** | `/api/user/notes/{id}` | `PersonalNotePolicy` | `$user->id === $note->user_id` |
| **Course** | `/api/user/courses/{id}` | `CoursePolicy` | `$user->id === $course->user_id` |
| **Chapter** | `/api/user/chapters/{id}` | `ChapterPolicy` | `$user->id === $chapter->course->user_id` |
| **Lesson** | `/api/user/lessons/{id}` | `LessonPolicy` | `$user->id === $lesson->chapter->course->user_id` |
| **Tag** | `/api/user/tags/{id}` | `TagPolicy` | `$user->id === $tag->user_id` |
| **Bookmark** | `/api/user/bookmarks/{id}` | `BookmarkPolicy` | `$user->id === $bookmark->user_id` |
| **Search Query** | `/api/user/search` | N/A (Scoped Query) | `where('user_id', $user->id)` strictly enforced |

*Rule*: Never use an un-scoped `Model::find($id)` or trust client-provided `user_id`. Always query via `$request->user()->relationship()->findOrFail($id)`.

---

## 2. Passwordless OTP Authentication Security

Users authenticate without passwords via email-delivered One-Time Passwords (OTPs). The authentication protocol follows strict defensive standards:

### 2.1 OTP Lifecycle & Rules:
1. **Cryptographic Entropy**: Generated using PHP's cryptographically secure pseudo-random integer generator:
   ```php
   $otp = (string) random_int(100000, 999999);
   ```
2. **Never Stored as Plaintext**: OTPs are hashed using `Hash::make($otp)` before saving to the `otp_verifications` table.
3. **Strict Expiration**: Each OTP is valid for exactly **5 minutes** from generation.
4. **Single-Use Guarantee**: Once verified, the OTP record is marked `verified_at = now()` and deleted or permanently invalidated.
5. **Brute-Force & Attempt Throttling**:
   - Maximum **5 failed verification attempts** allowed per OTP code. If exceeded, the OTP is instantly invalidated.
   - Resend cooldown enforced: User must wait **60 seconds** before requesting a new OTP.
   - Email rate limit: Maximum **5 OTP requests per hour per IP / email address**.
6. **Account Enumeration Defense**:
   Registration and Login API endpoints return identical generic messages: *"If an account exists or can be created with this email, a verification code has been dispatched."*

---

## 3. Public Sharing & Data Quarantine

Articles can be shared publicly via a dedicated public share token:

1. **Cryptographic Share Slug**:
   A random, unguessable 32-character string (`Str::random(32)`) is assigned to `share_token` when visibility is switched to `public`.
2. **Isolated Public Controller**:
   Public routes are managed exclusively by `App\Http\Controllers\Public\PublicArticleController`.
3. **Strict Query Quarantine**:
   ```php
   $article = AggregatedContent::where('share_token', $token)
       ->where('visibility', 'public')
       ->where('status', 'completed')
       ->firstOrFail();
   ```
4. **Data Masking via Public Resource**:
   `PublicArticleResource` explicitly excludes:
   - User ID, name, and email address.
   - Internal database IDs of chunks and transcript logs.
   - Personal notes (`personal_notes`).
   - Bookmarking state (`bookmarks`).
   - Any private courses or lessons.

---

## 4. Administration & Privilege Separation

1. **Role-Based Middleware**:
   Admin routes are grouped under `/api/admin` with `auth:sanctum` and `role:admin` middleware checks (`$request->user()->role === 'admin'`).
2. **Privacy Audit Logging**:
   Platform administrators can monitor system health, API quota errors, queue statistics, and aggregated counts. If an administrator views platform logs, each inspection is audited in `activity_logs` with timestamp, admin ID, and client IP.
3. **No Backdoor Tenant Impersonation**:
   Admins cannot modify or read private user notes.
4. **Zero AI Key Exposure to Admins**:
   User AI API keys (Groq & Gemini) are encrypted per-user; administrators cannot view, copy, or use user keys. Content generation runs strictly within the requesting user's security boundary.

---

## 5. Input Sanitization & Attack Mitigations

1. **SQL Injection**: Handled via PDO prepared statements and Eloquent parameter binding.
2. **Cross-Site Scripting (XSS)**:
   - Generated markdown is sanitized on the frontend using DOMPurify before rendering into HTML.
   - Raw user input in personal notes is escaped.
3. **Cross-Site Request Forgery (CSRF)**:
   - SPA requests send Laravel's `X-XSRF-TOKEN` cookie header.
4. **Mass Assignment**:
   - All Eloquent models utilize explicit `$fillable` arrays; `$guarded = []` is strictly forbidden.

---

## 6. Bring Your Own Key (BYOK) Per-User AI Key Encryption & Isolation

1. **At-Rest AES-256 Encryption**:
   - Every user's Groq and Gemini API keys are encrypted using Laravel's `'encrypted'` model cast (`AES-256-CBC` with MAC verification) in `user_ai_settings`.
   - Raw database dumps reveal only high-entropy ciphertext, never plaintext keys.
2. **Strict User Boundary**:
   - Keys are scoped `1:1` to `user_id`. User A's key can never be invoked by User B.
   - Frontend APIs return only masked strings (`gsk_••••••••12ab`, `AIza••••••••34cd`).
3. **Admin Monitoring Boundary**:
   - Administrators monitor aggregate content processing, latency, and system error rates, but cannot trigger content generation using user keys or access user keys.


