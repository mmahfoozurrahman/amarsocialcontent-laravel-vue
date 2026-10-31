<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import api from '@/services/api';
import EmbeddedYouTubePlayer from '@/components/content/EmbeddedYouTubePlayer.vue';

const route = useRoute();
const shareToken = computed(() => route.params.shareToken);

const article = ref(null);
const isLoading = ref(true);
const isNotFound = ref(false);

const fetchPublicArticle = async () => {
    isLoading.value = true;
    try {
        const res = await api.get(`/public/articles/${shareToken.value}`);
        article.value = res.data.data;
    } catch (err) {
        isNotFound.value = true;
    } finally {
        isLoading.value = false;
    }
};

const renderedHtml = computed(() => {
    const raw = article.value?.article?.raw_markdown || '';
    if (!raw) return '<p class="text-muted">কোনো কনটেন্ট পাওয়া যায়নি।</p>';
    return DOMPurify.sanitize(marked.parse(raw));
});

onMounted(() => {
    fetchPublicArticle();
});
</script>

<template>
    <div class="public-article-page py-4">
        <div class="container">
            <!-- Loading -->
            <div v-if="isLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">পাবলিক আর্টিকেল লোড হচ্ছে...</p>
            </div>

            <!-- 404 Not Found -->
            <div v-else-if="isNotFound" class="card border-0 shadow-sm p-5 text-center bg-white rounded-4 max-w-600 mx-auto">
                <i class="bi bi-shield-slash text-danger fs-1 mb-2"></i>
                <h4 class="fw-bold text-dark">আর্টিকেলটি পাওয়া যায়নি বা প্রাইভেট করা হয়েছে</h4>
                <p class="text-secondary small mb-3">
                    এই আর্টিকেলটি হয়তো লেখক ব্যক্তিগত রেখেছেন অথবা শেয়ার লিঙ্কটি আর সক্রিয় নেই।
                </p>
                <div>
                    <router-link to="/" class="btn btn-primary btn-sm px-4">
                        হোমপেজে ফিরে যান
                    </router-link>
                </div>
            </div>

            <!-- Article Found -->
            <div v-else class="row justify-content-center">
                <div class="col-lg-9">
                    <!-- Embedded In-App Player -->
                    <EmbeddedYouTubePlayer
                        :video-id="article.youtube_video_id"
                        :content-type="article.content_type"
                        :title="article.title"
                    />

                    <!-- Article Paper Card -->
                    <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4 mb-4">
                        <div class="border-bottom pb-4 mb-4">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-globe2 me-1"></i> পাবলিক শেয়ারড লার্নিং ম্যাটেরিয়াল
                                </span>
                                <span class="badge bg-primary-subtle text-primary">
                                    <i class="bi bi-clock me-1"></i> {{ article.reading_time_minutes }} মিনিট
                                </span>
                            </div>

                            <h1 class="fw-bold text-dark mb-3 fs-2">
                                {{ article.bengali_title || article.title }}
                            </h1>

                            <div class="d-flex flex-wrap align-items-center gap-3 text-secondary small">
                                <span><i class="bi bi-youtube text-danger me-1"></i> চ্যানেল: {{ article.channel_name }}</span>
                                <span><i class="bi bi-calendar3 me-1"></i> প্রকাশিত: {{ article.created_at }}</span>
                            </div>

                            <!-- Tags -->
                            <div v-if="article.tags && article.tags.length > 0" class="d-flex flex-wrap gap-1 mt-3">
                                <span v-for="t in article.tags" :key="t.name" class="badge bg-light text-secondary border">
                                    #{{ t.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Rendered Article -->
                        <div class="markdown-body" v-html="renderedHtml"></div>
                    </div>

                    <!-- Call To Action Box -->
                    <div class="card border-0 shadow-sm p-4 bg-primary text-white rounded-4 text-center">
                        <h5 class="fw-bold mb-2">আপনিও কি নিজের ব্যক্তিগত সেকেন্ড ব্রেন তৈরি করতে চান?</h5>
                        <p class="small text-white-50 mb-3 max-w-500 mx-auto">
                            AmarSocialContent ব্যবহার করে ইউটিউব ভিডিও এবং শর্টস থেকে জ্ঞান সংরক্ষণ করুন সম্পূর্ণ সহজবোধ্য বাংলায়।
                        </p>
                        <div>
                            <router-link to="/login" class="btn btn-light text-primary fw-semibold px-4 btn-sm">
                                বিনামূল্যে শুরু করুন
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.max-w-600 {
    max-width: 600px;
}
.max-w-500 {
    max-width: 500px;
}
</style>

