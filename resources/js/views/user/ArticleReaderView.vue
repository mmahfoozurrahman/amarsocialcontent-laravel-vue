<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import api from '@/services/api';
import { useNotification } from '@/composables/useNotification';
import EmbeddedYouTubePlayer from '@/components/content/EmbeddedYouTubePlayer.vue';
import TagSelector from '@/components/content/TagSelector.vue';
import PersonalNotesDrawer from '@/components/content/PersonalNotesDrawer.vue';

const route = useRoute();
const router = useRouter();
const { success, error, info } = useNotification();

const contentId = computed(() => route.params.id);
const article = ref(null);
const isLoading = ref(true);

// Edit States
const isEditingMarkdown = ref(false);
const editedMarkdown = ref('');
const isEditingTitle = ref(false);
const editedTitle = ref('');
const isSaving = ref(false);

// Reading Tracker Timer
let readInterval = null;
const secondsStudied = ref(0);

// Fetch Article Details
const fetchArticle = async () => {
    isLoading.value = true;
    try {
        const res = await api.get(`/user/articles/${contentId.value}`);
        article.value = res.data.data;
        editedMarkdown.value = article.value.article?.raw_markdown || '';
        editedTitle.value = article.value.bengali_title || article.value.title;
        secondsStudied.value = article.value.reading_progress?.seconds_spent || 0;
    } catch (err) {
        error('আর্টিকেল লোড করা যায়নি।');
        router.push('/library');
    } finally {
        isLoading.value = false;
    }
};

// Render Markdown Safely
const renderedHtml = computed(() => {
    const raw = article.value?.article?.raw_markdown || '';
    if (!raw) return '<p class="text-muted">কোনো কনটেন্ট পাওয়া যায়নি।</p>';
    const html = marked.parse(raw);
    return DOMPurify.sanitize(html);
});

// Bookmark Toggle
const handleBookmarkToggle = async () => {
    try {
        const res = await api.post(`/user/articles/${contentId.value}/bookmark`);
        article.value.is_bookmarked = res.data.is_bookmarked;
        success(res.data.message);
    } catch (e) {
        error('বুকমার্ক পরিবর্তন করা যায়নি।');
    }
};

// Visibility Toggle (Private <-> Public)
const toggleVisibility = async () => {
    const newVisibility = article.value.visibility === 'public' ? 'private' : 'public';
    try {
        const res = await api.put(`/user/articles/${contentId.value}`, {
            visibility: newVisibility,
        });
        article.value.visibility = res.data.data.visibility;
        article.value.share_token = res.data.data.share_token;
        success(newVisibility === 'public' ? 'আর্টিকেলটি পাবলিক করা হয়েছে।' : 'আর্টিকেলটি প্রাইভেট করা হয়েছে।');
    } catch (e) {
        error('ভিজিবিলিটি আপডেট করা যায়নি।');
    }
};

// Copy Public Link
const copyPublicLink = () => {
    if (!article.value.share_token) return;
    const url = `${window.location.origin}/p/${article.value.share_token}`;
    navigator.clipboard.writeText(url);
    info('পাবলিক লিঙ্ক ক্লিপবোর্ডে কপি করা হয়েছে!');
};

// Save Title Edit
const saveTitle = async () => {
    if (!editedTitle.value.trim()) return;
    isSaving.value = true;
    try {
        const res = await api.put(`/user/articles/${contentId.value}`, {
            bengali_title: editedTitle.value.trim(),
        });
        article.value.bengali_title = res.data.data.bengali_title;
        isEditingTitle.value = false;
        success('শিরোনাম আপডেট করা হয়েছে।');
    } catch (e) {
        error('শিরোনাম সেভ করা যায়নি।');
    } finally {
        isSaving.value = false;
    }
};

// Save Markdown Edit
const saveMarkdown = async () => {
    isSaving.value = true;
    try {
        const res = await api.put(`/user/articles/${contentId.value}`, {
            raw_markdown: editedMarkdown.value,
        });
        article.value.article.raw_markdown = res.data.data.article.raw_markdown;
        isEditingMarkdown.value = false;
        success('আর্টিকেল সফলভাবে আপডেট করা হয়েছে।');
    } catch (e) {
        error('আপডেট করা যায়নি।');
    } finally {
        isSaving.value = false;
    }
};

// Toggle Completion Status
const toggleCompletion = async () => {
    const currentCompleted = article.value.reading_progress?.is_completed || false;
    try {
        const res = await api.post(`/user/articles/${contentId.value}/reading-progress`, {
            is_completed: !currentCompleted,
        });
        if (!article.value.reading_progress) {
            article.value.reading_progress = {};
        }
        article.value.reading_progress.is_completed = res.data.data.is_completed;
        success(res.data.data.is_completed ? 'পড়া সম্পন্ন হিসেবে চিহ্নিত করা হয়েছে!' : 'অসম্পন্ন হিসেবে চিহ্নিত করা হয়েছে।');
    } catch (e) {
        error('স্ট্যাটাস আপডেট করা যায়নি।');
    }
};

// Periodic Reading Tracker (Every 30 seconds)
const startReadingTracker = () => {
    readInterval = setInterval(async () => {
        try {
            await api.post(`/user/articles/${contentId.value}/reading-progress`, {
                additional_seconds: 30,
            });
            secondsStudied.value += 30;
        } catch (e) {
            // Ignore background tracker failure
        }
    }, 30000);
};

onMounted(async () => {
    await fetchArticle();
    startReadingTracker();
});

onUnmounted(() => {
    if (readInterval) clearInterval(readInterval);
});
</script>

<template>
    <div class="article-reader-page py-4">
        <div class="container">
            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">আর্টিকেল লোড হচ্ছে...</p>
            </div>

            <div v-else-if="article" class="row g-4">
                <!-- Left Main Column: Video Player & Article Content -->
                <div class="col-lg-8">
                    <!-- Top Action / Navigation Bar -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <router-link to="/library" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> লাইব্রেরিতে ফিরে যান
                        </router-link>

                        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                            <!-- Bookmark Button -->
                            <button
                                class="btn btn-sm"
                                :class="article.is_bookmarked ? 'btn-warning text-dark' : 'btn-outline-secondary'"
                                title="বুকমার্ক"
                                @click="handleBookmarkToggle"
                            >
                                <i class="bi" :class="article.is_bookmarked ? 'bi-bookmark-star-fill' : 'bi-bookmark'"></i>
                                <span class="d-none d-sm-inline ms-1">{{ article.is_bookmarked ? 'বুকমার্কড' : 'বুকমার্ক' }}</span>
                            </button>

                            <!-- Public / Private Toggle -->
                            <button
                                class="btn btn-sm"
                                :class="article.visibility === 'public' ? 'btn-success' : 'btn-outline-secondary'"
                                :title="article.visibility === 'public' ? 'পাবলিক করা আছে' : 'প্রাইভেট করা আছে'"
                                @click="toggleVisibility"
                            >
                                <i class="bi" :class="article.visibility === 'public' ? 'bi-globe2' : 'bi-lock'"></i>
                                <span class="d-none d-sm-inline ms-1">{{ article.visibility === 'public' ? 'Public' : 'Private' }}</span>
                            </button>

                            <!-- Copy Share Link if Public -->
                            <button
                                v-if="article.visibility === 'public' && article.share_token"
                                class="btn btn-outline-primary btn-sm"
                                title="পাবলিক শেয়ার লিঙ্ক কপি করুন"
                                @click="copyPublicLink"
                            >
                                <i class="bi bi-link-45deg"></i>
                                <span class="d-none d-sm-inline ms-1">লিঙ্ক কপি</span>
                            </button>

                            <!-- Mark as Completed Toggle -->
                            <button
                                class="btn btn-sm"
                                :class="article.reading_progress?.is_completed ? 'btn-success' : 'btn-outline-success'"
                                @click="toggleCompletion"
                            >
                                <i class="bi" :class="article.reading_progress?.is_completed ? 'bi-check2-circle' : 'bi-circle'"></i>
                                <span class="ms-1">{{ article.reading_progress?.is_completed ? 'পড়া সম্পন্ন' : 'Mark as Read' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- In-App Embedded YouTube Player -->
                    <EmbeddedYouTubePlayer
                        :video-id="article.youtube_video_id"
                        :content-type="article.content_type"
                        :title="article.title"
                    />

                    <!-- Article Card -->
                    <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4">
                        <!-- Bengali Title Header -->
                        <div class="border-bottom pb-4 mb-4">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="badge bg-primary-subtle text-primary">
                                    <i class="bi bi-clock me-1"></i> {{ article.reading_time_minutes }} মিনিট পড়ার সময়
                                </span>
                                <span v-if="article.ai_provider" class="badge bg-light text-secondary border">
                                    <i class="bi bi-stars text-warning me-1"></i> {{ article.ai_provider }} ({{ article.ai_model }})
                                </span>
                                <span v-if="secondsStudied > 0" class="badge bg-warning-subtle text-dark">
                                    <i class="bi bi-hourglass-split me-1"></i> আপনি পড়েছেন: {{ Math.round(secondsStudied / 60) }} মিনিট
                                </span>
                            </div>

                            <!-- Title with Inline Edit -->
                            <div v-if="!isEditingTitle" class="d-flex justify-content-between align-items-start gap-2">
                                <h1 class="fw-bold text-dark mb-0 fs-3">
                                    {{ article.bengali_title || article.title }}
                                </h1>
                                <button class="btn btn-link btn-sm text-muted p-0 ms-2" title="শিরোনাম পরিবর্তন" @click="isEditingTitle = true">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                            <div v-else class="mt-2">
                                <div class="input-group">
                                    <input v-model="editedTitle" type="text" class="form-control" />
                                    <button class="btn btn-primary btn-sm" :disabled="isSaving" @click="saveTitle">সেভ</button>
                                    <button class="btn btn-outline-secondary btn-sm" @click="isEditingTitle = false">বাতিল</button>
                                </div>
                            </div>

                            <!-- Channel and Original Link -->
                            <div class="d-flex flex-wrap align-items-center gap-3 text-secondary small mt-3">
                                <span><i class="bi bi-youtube text-danger me-1"></i> চ্যানেল: {{ article.channel_name }}</span>
                                <a :href="article.youtube_url" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> মূল ইউটিউব ভিডিও দেখুন
                                </a>
                            </div>
                        </div>

                        <!-- Markdown Content Area -->
                        <div class="article-content">
                            <!-- Toggle Edit Mode Button -->
                            <div class="d-flex justify-content-end mb-3">
                                <button
                                    class="btn btn-outline-secondary btn-sm"
                                    @click="isEditingMarkdown = !isEditingMarkdown"
                                >
                                    <i class="bi" :class="isEditingMarkdown ? 'bi-eye' : 'bi-pencil-square'"></i>
                                    <span class="ms-1">{{ isEditingMarkdown ? 'প্রিভিউ দেখুন' : 'আর্টিকেল এডিট করুন' }}</span>
                                </button>
                            </div>

                            <!-- View Mode: Rendered HTML -->
                            <div
                                v-if="!isEditingMarkdown"
                                class="markdown-body prose"
                                v-html="renderedHtml"
                            ></div>

                            <!-- Edit Mode: Markdown Textarea -->
                            <div v-else>
                                <textarea
                                    v-model="editedMarkdown"
                                    rows="16"
                                    class="form-control font-monospace mb-3"
                                ></textarea>
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-outline-secondary btn-sm" @click="isEditingMarkdown = false">বাতিল</button>
                                    <button class="btn btn-primary btn-sm px-4" :disabled="isSaving" @click="saveMarkdown">
                                        <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                                        আপডেট সংরক্ষণ করুন
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side Column: Meta, Tags, Notes, TOC -->
                <div class="col-lg-4">
                    <!-- Personal Notes Drawer -->
                    <PersonalNotesDrawer
                        :content-id="article.id"
                        :initial-note="article.personal_note || ''"
                    />

                    <!-- Tag Selector -->
                    <div class="card border-0 shadow-sm p-3 mb-4 bg-white rounded-3">
                        <TagSelector
                            :content-id="article.id"
                            :initial-tags="article.tags || []"
                        />
                    </div>

                    <!-- Transcript Modal / Info Accordion -->
                    <div class="card border-0 shadow-sm p-3 bg-white rounded-3 mb-4">
                        <h6 class="fw-bold mb-2 small text-dark">
                            <i class="bi bi-file-earmark-text text-primary me-1"></i>মূল ট্রান্সক্রিপ্ট তথ্য
                        </h6>
                        <div class="smaller text-muted mb-2">
                            ভাষা: <span class="badge bg-light text-dark border">{{ article.transcript?.language || 'en' }}</span>
                            <span v-if="article.transcript?.is_manual" class="badge bg-info-subtle text-info ms-1">ম্যানুয়াল</span>
                        </div>
                        <div v-if="article.transcript?.full_transcript" class="p-2 bg-light rounded border smaller text-secondary" style="max-height: 180px; overflow-y: auto;">
                            {{ article.transcript.full_transcript }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Markdown Typography Styles */
.markdown-body {
    line-height: 1.8;
    color: #334155;
    font-size: 1.05rem;
}
.markdown-body h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
}
.markdown-body h2 {
    font-size: 1.4rem;
    font-weight: 600;
    color: #1e293b;
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.35rem;
}
.markdown-body h3 {
    font-size: 1.15rem;
    font-weight: 600;
    color: #334155;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
}
.markdown-body p {
    margin-bottom: 1.15rem;
}
.markdown-body ul, .markdown-body ol {
    margin-bottom: 1.15rem;
    padding-left: 1.5rem;
}
.markdown-body li {
    margin-bottom: 0.4rem;
}
.markdown-body code {
    background-color: #f1f5f9;
    color: #2563eb;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.9em;
}
.markdown-body pre {
    background-color: #0f172a;
    color: #f8fafc;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
}
.markdown-body blockquote {
    border-left: 4px solid #2563eb;
    padding-left: 1rem;
    color: #64748b;
    margin-left: 0;
    font-style: italic;
}
.smaller {
    font-size: 0.8rem;
}
</style>

