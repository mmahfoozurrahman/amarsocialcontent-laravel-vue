<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useSearch } from '@/composables/useSearch';
import VideoDetailsModal from '@/components/video/VideoDetailsModal.vue';

const router = useRouter();
const route = useRoute();
const videoModalRef = ref(null);
const {
    query,
    searchType,
    contentType,
    results,
    meta,
    isLoading,
    searchHistory,
    executeSearch,
    fetchHistory,
    clearHistory,
} = useSearch();

const syncUrl = () => {
    const qParams = {};
    if (query.value.trim()) {
        qParams.q = query.value.trim();
        if (searchType.value !== 'hybrid') qParams.type = searchType.value;
        if (contentType.value !== 'all') qParams.content_type = contentType.value;
    }
    router.replace({ query: qParams });
};

const handleSearch = (newQuery = null) => {
    if (newQuery !== null) {
        query.value = newQuery;
    }
    syncUrl();
    executeSearch();
};

onMounted(() => {
    fetchHistory();
    // Restore search state from URL query params on reload/refresh
    if (route.query.q) {
        query.value = String(route.query.q);
        if (route.query.type) searchType.value = String(route.query.type);
        if (route.query.content_type) contentType.value = String(route.query.content_type);
        executeSearch();
    }
});

// Re-search when search type or content type filter changes
watch([searchType, contentType], () => {
    if (query.value.trim()) {
        handleSearch();
    }
});

let debounceTimer = null;
const handleInput = () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        handleSearch();
    }, 400);
};

const handleSelectHistory = (historyQuery) => {
    query.value = historyQuery;
    handleSearch();
};

const handleReset = () => {
    query.value = '';
    searchType.value = 'hybrid';
    contentType.value = 'all';
    results.value = [];
    meta.value = { query: '', total: 0, search_type: 'hybrid' };
    router.replace({ query: {} });
};

const openArticle = (id) => {
    router.push(`/articles/${id}`);
};

const openVideoModal = (item) => {
    videoModalRef.value?.openModal(item);
};
</script>

<template>
    <div class="search-view bg-light min-vh-100 pb-5">
        <div class="container py-4">
            <!-- Header Section -->
            <div class="row justify-content-center mb-4">
                <div class="col-lg-9 col-xl-8 text-center">
                    <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-2">
                        <i class="bi bi-stars me-1"></i> এআই সেমান্টিক ও হাইব্রিড সার্চ
                    </span>
                    <h2 class="fw-bold text-dark mb-2">জ্ঞানভাণ্ডার থেকে যে কোনো বিষয় খুঁজুন</h2>
                    <p class="text-secondary small mb-4">
                        বাংলা বা ইংরেজিতে কোনো কনসেপ্ট, সমস্যা বা প্রাসঙ্গিক কীওয়ার্ড লিখুন—এআই ভাবার্থ বুঝে সঠিক ভিডিওর আর্টিকেল বের করে আনবে।
                    </p>

                    <!-- Google-Style Search Box -->
                    <div class="card border-0 shadow-sm rounded-4 p-2 bg-white mb-3">
                        <div class="input-group input-group-lg border-0 align-items-center px-2">
                            <span class="bg-transparent border-0 text-primary fs-4 pe-2">
                                <i v-if="!isLoading" class="bi bi-search"></i>
                                <span v-else class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            </span>
                            <input
                                v-model="query"
                                type="text"
                                class="form-control border-0 shadow-none fs-5 py-2"
                                placeholder="উদাঃ AI Agent এর সুবিধা, ম্যাকবুকের ফিচার, সফটওয়্যার ক্যারিয়ার..."
                                autofocus
                                @input="handleInput"
                                @keyup.enter="handleSearch()"
                            />
                            
                            <!-- Clear / Reset Button inside search bar -->
                            <button
                                v-if="query || results.length > 0"
                                type="button"
                                class="btn btn-outline-secondary btn-sm rounded-3 px-3 py-2 fw-semibold me-2 d-inline-flex align-items-center gap-1"
                                @click="handleReset"
                                title="সার্চ রিসেট করে আগের অবস্থায় ফিরুন"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span class="d-none d-sm-inline">রিসেট</span>
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary rounded-3 px-4 fw-semibold"
                                :disabled="isLoading || !query.trim()"
                                @click="handleSearch()"
                            >
                                খুঁজুন
                            </button>
                        </div>
                    </div>

                    <!-- Search Filter Controls -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-1">
                        <!-- Search Type Pills -->
                        <div class="btn-group btn-group-sm rounded-pill p-1 bg-white border shadow-xs" role="group">
                            <button
                                type="button"
                                class="btn rounded-pill px-3 py-1 fw-semibold transition-all"
                                :class="searchType === 'hybrid' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary border-0'"
                                @click="searchType = 'hybrid'"
                            >
                                <i class="bi bi-lightning-charge me-1"></i> হাইব্রিড (সেরা ফলাফল)
                            </button>
                            <button
                                type="button"
                                class="btn rounded-pill px-3 py-1 fw-semibold transition-all"
                                :class="searchType === 'semantic' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary border-0'"
                                @click="searchType = 'semantic'"
                            >
                                <i class="bi bi-brain me-1"></i> ভাবার্থ (Semantic)
                            </button>
                            <button
                                type="button"
                                class="btn rounded-pill px-3 py-1 fw-semibold transition-all"
                                :class="searchType === 'fulltext' ? 'btn-primary text-white shadow-sm' : 'btn-light text-secondary border-0'"
                                @click="searchType = 'fulltext'"
                            >
                                <i class="bi bi-fonts me-1"></i> হুবহু শব্দ (Fulltext)
                            </button>
                        </div>

                        <!-- Content Type Selector -->
                        <div class="d-flex align-items-center gap-1">
                            <button
                                type="button"
                                class="btn btn-sm rounded-pill px-3"
                                :class="contentType === 'all' ? 'btn-primary fw-semibold' : 'bg-white border text-secondary'"
                                @click="contentType = 'all'"
                            >
                                সব
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm rounded-pill px-3"
                                :class="contentType === 'video' ? 'btn-primary fw-semibold' : 'bg-white border text-secondary'"
                                @click="contentType = 'video'"
                            >
                                <i class="bi bi-play-circle me-1"></i> ভিডিও
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm rounded-pill px-3"
                                :class="contentType === 'short' ? 'btn-primary fw-semibold' : 'bg-white border text-secondary'"
                                @click="contentType = 'short'"
                            >
                                <i class="bi bi-lightning text-danger me-1"></i> শর্টস
                            </button>
                        </div>
                    </div>

                    <!-- Recent Searches / Suggestions (When Query is empty) -->
                    <div v-if="!query && searchHistory.length > 0" class="mt-4 text-start bg-white p-3 rounded-4 border shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted">
                                <i class="bi bi-clock-history me-1"></i> সাম্প্রতিক সার্চ:
                            </span>
                            <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none p-0 smaller" @click="clearHistory">
                                হিস্ট্রি মুছুন
                            </button>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button
                                v-for="item in searchHistory"
                                :key="item.id"
                                type="button"
                                class="btn btn-sm btn-light border rounded-pill px-3 py-1 small text-dark d-flex align-items-center gap-1"
                                @click="handleSelectHistory(item.query)"
                            >
                                <i class="bi bi-search text-muted smaller"></i>
                                <span>{{ item.query }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">
                    <!-- Results Count Banner & Reset Action -->
                    <div v-if="query && !isLoading" class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 px-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-secondary">
                                "<strong>{{ meta.query || query }}</strong>" এর জন্য 
                                <span class="fw-bold text-primary">{{ meta.total }}</span> টি ফলাফল পাওয়া গেছে:
                            </span>
                            <button
                                type="button"
                                class="btn btn-link btn-sm text-danger text-decoration-none p-0 smaller fw-semibold d-inline-flex align-items-center gap-1"
                                @click="handleReset"
                                title="সার্চ রিসেট করুন"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i> রিসেট করুন
                            </button>
                        </div>
                        <span class="badge bg-light text-secondary border smaller">
                            {{ searchType === 'hybrid' ? 'হাইব্রিড র‍্যাংকিং' : searchType === 'semantic' ? 'সেমান্টিক ভেক্টর র‍্যাংকিং' : 'টেক্সট ম্যাচিং' }}
                        </span>
                    </div>

                    <!-- Loading State -->
                    <div v-if="isLoading" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted small">এআই ভেক্টর ও কীওয়ার্ড বিশ্লেষণ চলছে...</p>
                    </div>

                    <!-- Empty State: No Results Found -->
                    <div v-else-if="query && results.length === 0" class="card border-0 shadow-sm rounded-4 text-center p-5 bg-white">
                        <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle text-muted mx-auto mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-search fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">কোনো প্রাসঙ্গিক তথ্য পাওয়া যায়নি</h5>
                        <p class="text-secondary small mb-3">
                            অন্য কোনো শব্দ বা সহজ বাক্যাংশে সার্চ করে দেখুন অথবা "হাইব্রিড" মোড নির্বাচন করুন।
                        </p>
                        <div>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" @click="handleReset">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> সার্চ রিসেট করুন
                            </button>
                        </div>
                    </div>

                    <!-- Results List -->
                    <div v-else-if="results.length > 0" class="d-flex flex-column gap-3">
                        <div
                            v-for="item in results"
                            :key="item.id"
                            class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white result-card transition-all"
                            @click="openArticle(item.id)"
                        >
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex gap-3 align-items-start">
                                    <!-- Thumbnail -->
                                    <div
                                        class="position-relative flex-shrink-0 rounded-3 overflow-hidden cursor-pointer"
                                        style="width: 120px; height: 75px;"
                                        title="ইন-অ্যাপ ভিডিও প্লেয়ার খুলুন"
                                        @click.stop="openVideoModal(item)"
                                    >
                                        <img
                                            :src="item.thumbnail_url"
                                            :alt="item.bengali_title"
                                            class="w-100 h-100 object-fit-cover"
                                        />
                                        <span
                                            class="position-absolute bottom-0 end-0 badge m-1"
                                            :class="item.content_type === 'short' ? 'bg-danger' : 'bg-dark'"
                                        >
                                            {{ item.content_type === 'short' ? 'Short' : `${item.reading_time_minutes}m` }}
                                        </span>
                                    </div>

                                    <!-- Content Info -->
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <!-- Relevance Badge -->
                                            <span
                                                class="badge rounded-pill px-2 py-1"
                                                :class="{
                                                    'bg-success-subtle text-success border border-success-subtle': item.relevance_percent >= 60,
                                                    'bg-primary-subtle text-primary border border-primary-subtle': item.relevance_percent >= 35 && item.relevance_percent < 60,
                                                    'bg-secondary-subtle text-secondary border border-secondary-subtle': item.relevance_percent < 35
                                                }"
                                            >
                                                <i class="bi bi-bullseye me-1"></i> {{ item.relevance_percent }}% প্রাসঙ্গিক
                                            </span>

                                            <!-- Matched Type -->
                                            <span class="badge bg-light text-muted border smaller">
                                                {{ item.matched_type === 'semantic' ? 'ভাবার্থ মিল' : 'কীওয়ার্ড মিল' }}
                                            </span>

                                            <span class="text-muted smaller ms-auto">
                                                <i class="bi bi-person me-1"></i> {{ item.channel_name }}
                                            </span>
                                        </div>

                                        <!-- Bengali Title -->
                                        <h5 class="fw-bold text-dark mb-1 title-hover leading-snug">
                                            {{ item.bengali_title }}
                                        </h5>

                                        <!-- Highlighted Excerpt with <mark> -->
                                        <div
                                            v-if="item.excerpt"
                                            class="search-excerpt text-secondary small mb-2 p-2 bg-light rounded-3"
                                            v-html="item.excerpt"
                                        ></div>

                                        <!-- Tags & Action -->
                                        <div class="d-flex align-items-center justify-content-between mt-2 pt-1">
                                            <div class="d-flex flex-wrap gap-1">
                                                <span
                                                    v-for="tag in item.tags"
                                                    :key="tag.id"
                                                    class="badge bg-light text-secondary border smaller"
                                                >
                                                    #{{ tag.name }}
                                                </span>
                                            </div>

                                            <div class="d-flex align-items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger btn-sm py-0 px-2 smaller d-inline-flex align-items-center gap-1"
                                                    title="ইন-অ্যাপ ভিডিও প্লেয়ার ও বিস্তারিত তথ্য দেখুন"
                                                    @click.stop="openVideoModal(item)"
                                                >
                                                    <i class="bi bi-youtube"></i> ভিডিও
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 fw-semibold smaller">
                                                    আর্টিকেল পড়ুন <i class="bi bi-arrow-right ms-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome Intro (When nothing is searched yet) -->
                    <div v-else class="text-center py-5">
                        <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 64px; height: 64px;">
                                <i class="bi bi-lightbulb fs-2"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">স্মার্ট এআই সেমান্টিক সার্চ কীভাবে কাজ করে?</h5>
                            <p class="text-secondary small mx-auto mb-4" style="max-width: 500px;">
                                আপনাকে হুবহু শিরোনাম বা শব্দ মনে রাখতে হবে না। আপনার পছন্দের যে কোনো চিন্তা, টেকনিক্যাল প্রশ্ন বা বিষয় লিখে সার্চ করুন।
                            </p>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <button
                                    type="button"
                                    class="btn btn-light border rounded-pill px-3 py-1 small"
                                    @click="handleSelectHistory('ম্যাকবুক ডিসপ্লে')"
                                >
                                    💡 ম্যাকবুক ডিসপ্লে
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-light border rounded-pill px-3 py-1 small"
                                    @click="handleSelectHistory('AI Agent')"
                                >
                                    💡 AI Agent
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-light border rounded-pill px-3 py-1 small"
                                    @click="handleSelectHistory('সফটওয়্যার ক্যারিয়ার')"
                                >
                                    💡 সফটওয়্যার ক্যারিয়ার
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- In-App Video Details Modal -->
        <VideoDetailsModal ref="videoModalRef" />
    </div>
</template>

<style scoped>
.result-card {
    cursor: pointer;
    border: 1px solid var(--border-default);
}
.result-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--card-shadow-hover) !important;
    border-color: var(--border-hover);
}
.result-card:hover .title-hover {
    color: var(--brand-primary) !important;
}
.leading-snug {
    line-height: 1.35;
}
.smaller {
    font-size: 0.78rem;
}
.transition-all {
    transition: all 0.2s ease-in-out;
}
:deep(mark) {
    background-color: #fff8eb !important;
    color: #b45309 !important;
    padding: 0 3px;
    border-radius: 3px;
    font-weight: 600;
}
</style>
