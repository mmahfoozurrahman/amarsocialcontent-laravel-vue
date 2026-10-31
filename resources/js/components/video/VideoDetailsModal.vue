<script setup>
import { ref, watch, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import StatusBadge from '@/components/content/StatusBadge.vue';

const router = useRouter();

const show = ref(false);
const content = ref(null);
const fullDetails = ref(null);
const isLoadingDetails = ref(false);

const isShort = computed(() => {
    return content.value?.content_type === 'short';
});

const embedUrl = computed(() => {
    if (!content.value?.youtube_video_id) return '';
    return `https://www.youtube-nocookie.com/embed/${content.value.youtube_video_id}?rel=0&enablejsapi=1`;
});

// All tags combining content tags and channel tag
const displayTags = computed(() => {
    const list = [];
    if (fullDetails.value?.tags) {
        list.push(...fullDetails.value.tags);
    } else if (content.value?.tags) {
        list.push(...content.value.tags);
    }
    return list;
});

const openModal = async (item) => {
    content.value = item;
    fullDetails.value = null;
    show.value = true;

    // Fetch full article details (takeaways, raw_markdown, full tags) if available
    if (item?.id) {
        isLoadingDetails.value = true;
        try {
            const res = await api.get(`/user/articles/${item.id}`);
            fullDetails.value = res.data.data;
        } catch (e) {
            // Keep preview info from item
        } finally {
            isLoadingDetails.value = false;
        }
    }
};

const closeModal = () => {
    show.value = false;
    content.value = null;
    fullDetails.value = null;
};

const navigateToArticle = () => {
    if (content.value?.id) {
        const id = content.value.id;
        closeModal();
        router.push(`/articles/${id}`);
    }
};

defineExpose({ openModal, closeModal });
</script>

<template>
    <div>
        <!-- Modal Backdrop -->
        <div v-if="show" class="modal-backdrop fade show" @click="closeModal"></div>

        <!-- Modal Dialog -->
        <div
            v-if="show && content"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            @click.self="closeModal"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <!-- Modal Header -->
                    <div class="modal-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2 min-w-0 pe-2">
                            <span class="bg-danger text-white rounded-3 p-2 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px;">
                                <i class="bi bi-youtube fs-6"></i>
                            </span>
                            <div class="min-w-0">
                                <h5 class="modal-title fw-bold text-dark text-truncate mb-0" style="font-size: 1.05rem;" :title="content.bengali_title || content.title">
                                    {{ content.bengali_title || content.title }}
                                </h5>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-light text-secondary border smaller">
                                        <i class="bi bi-person-fill text-primary me-1"></i>{{ content.channel_name }}
                                    </span>
                                    <span v-if="isShort" class="badge bg-danger-subtle text-danger smaller">
                                        <i class="bi bi-lightning-fill"></i> YouTube Shorts
                                    </span>
                                    <span v-else class="badge bg-primary-subtle text-primary smaller">
                                        <i class="bi bi-play-circle-fill"></i> ভিডিও (16:9)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn-close flex-shrink-0"
                            aria-label="Close"
                            @click="closeModal"
                        ></button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body p-0">
                        <!-- Embedded YouTube Player -->
                        <div class="player-wrapper bg-black text-center p-2 p-md-3">
                            <div
                                class="video-container mx-auto rounded-3 overflow-hidden shadow"
                                :class="isShort ? 'shorts-player-box' : 'video-player-box'"
                            >
                                <iframe
                                    :src="embedUrl"
                                    class="w-100 h-100 border-0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                ></iframe>
                            </div>
                        </div>

                        <!-- Content Details Section -->
                        <div class="p-4 bg-light">
                            <!-- Titles & Channel Info Card -->
                            <div class="card border-0 shadow-xs rounded-3 p-3 bg-white mb-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="small text-muted fw-semibold">
                                        মূল ইউটিউব শিরোনাম:
                                    </div>
                                    <StatusBadge v-if="content.status" :status="content.status" />
                                </div>
                                <h6 class="fw-semibold text-dark mb-2">
                                    {{ content.title }}
                                </h6>
                                <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
                                    <span>
                                        <i class="bi bi-person-video2 text-primary me-1"></i> {{ content.channel_name }}
                                    </span>
                                    <span>
                                        <i class="bi bi-clock me-1"></i> {{ content.reading_time_minutes || fullDetails?.reading_time_minutes || 3 }} মিনিট পড়ার সময়
                                    </span>
                                    <span v-if="content.relevance_percent" class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-bullseye me-1"></i> {{ content.relevance_percent }}% প্রাসঙ্গিক
                                    </span>
                                </div>
                            </div>

                            <!-- Tags Section (Includes Default Channel Tag) -->
                            <div class="card border-0 shadow-xs rounded-3 p-3 bg-white mb-3">
                                <div class="small fw-semibold text-secondary mb-2 d-flex align-items-center gap-1">
                                    <i class="bi bi-tags-fill text-primary"></i> যুক্ত ট্যাগসমূহ:
                                </div>
                                <div class="d-flex flex-wrap gap-1">
                                    <span
                                        v-for="t in displayTags"
                                        :key="t.id || t.name"
                                        class="badge bg-light text-secondary border px-2 py-1 smaller fw-medium"
                                        :class="{ 'border-primary text-primary bg-primary-subtle': t.name === content.channel_name }"
                                    >
                                        <i v-if="t.name === content.channel_name" class="bi bi-person-fill me-1"></i>
                                        #{{ t.name }}
                                    </span>
                                    <span v-if="displayTags.length === 0" class="text-muted smaller fst-italic">
                                        কোনো ট্যাগ নেই
                                    </span>
                                </div>
                            </div>

                            <!-- Summary / Key Takeaways / Excerpt -->
                            <div class="card border-0 shadow-xs rounded-3 p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small fw-semibold text-secondary d-flex align-items-center gap-1">
                                        <i class="bi bi-journal-text text-primary"></i> ভিডিও সারসংক্ষেপ ও অন্তর্দৃষ্টি:
                                    </div>
                                    <span v-if="isLoadingDetails" class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                </div>

                                <!-- If fullDetails has article takeaways -->
                                <div v-if="fullDetails?.article?.takeaways" class="article-takeaways small text-dark">
                                    <ul class="mb-0 ps-3">
                                        <li
                                            v-for="(point, idx) in (Array.isArray(fullDetails.article.takeaways) ? fullDetails.article.takeaways : [fullDetails.article.takeaways])"
                                            :key="idx"
                                            class="mb-1"
                                        >
                                            {{ point }}
                                        </li>
                                    </ul>
                                </div>

                                <!-- If introduction is available -->
                                <p v-else-if="fullDetails?.article?.introduction" class="small text-secondary mb-0 leading-relaxed">
                                    {{ fullDetails.article.introduction }}
                                </p>

                                <!-- Fallback to search excerpt or generic notice -->
                                <div
                                    v-else-if="content.excerpt"
                                    class="search-excerpt small text-secondary mb-0 p-2 bg-light rounded"
                                    v-html="content.excerpt"
                                ></div>

                                <p v-else class="small text-muted mb-0 fst-italic">
                                    এই ভিডিওটির সম্পূর্ণ বাংলা ব্যাখ্যা ও বিস্তারিত নোট পড়তে নিচের বাটনে ক্লিক করুন।
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer bg-white border-top py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <!-- External Link -->
                        <a
                            :href="content.youtube_url"
                            target="_blank"
                            class="btn btn-outline-danger btn-sm px-3 d-inline-flex align-items-center gap-1"
                            title="ইউটিউবে মূল ভিডিও খুলুন"
                        >
                            <i class="bi bi-box-arrow-up-right"></i> ইউটিউবে দেখুন
                        </a>

                        <div class="d-flex align-items-center gap-2">
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm px-3"
                                @click="closeModal"
                            >
                                বন্ধ করুন
                            </button>
                            <button
                                type="button"
                                class="btn btn-primary btn-sm px-3 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm"
                                @click="navigateToArticle"
                            >
                                <i class="bi bi-book-half"></i> সম্পূর্ণ বাংলা আর্টিকেল পড়ুন
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.78rem;
}

.leading-relaxed {
    line-height: 1.6;
}

.video-player-box {
    width: 100%;
    aspect-ratio: 16 / 9;
    max-height: 420px;
}

.shorts-player-box {
    width: 100%;
    max-width: 320px;
    aspect-ratio: 9 / 16;
    max-height: 520px;
}

.shadow-xs {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
</style>

