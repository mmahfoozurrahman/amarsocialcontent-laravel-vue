<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useNotification } from '@/composables/useNotification';
import AddContentModal from '@/components/content/AddContentModal.vue';
import StatusBadge from '@/components/content/StatusBadge.vue';
import VideoDetailsModal from '@/components/video/VideoDetailsModal.vue';

const router = useRouter();
const { success, error } = useNotification();

const addModalRef = ref(null);
const videoModalRef = ref(null);
const articles = ref([]);
const tags = ref([]);
const isLoading = ref(true);

// Filters State
const searchQuery = ref('');
const selectedTag = ref('');
const selectedType = ref(''); // '' | 'video' | 'short'
const selectedVisibility = ref(''); // '' | 'private' | 'public'
const onlyBookmarked = ref(false);

const fetchLibrary = async () => {
    isLoading.value = true;
    try {
        if (onlyBookmarked.value) {
            const res = await api.get('/user/bookmarks');
            articles.value = res.data.data;
        } else {
            const params = {};
            if (selectedType.value) params.content_type = selectedType.value;
            const res = await api.get('/user/articles', { params });
            articles.value = res.data.data;
        }
    } catch (err) {
        error('লাইব্রেরি লোড করতে সমস্যা হয়েছে।');
    } finally {
        isLoading.value = false;
    }
};

const fetchTags = async () => {
    try {
        const res = await api.get('/user/tags');
        tags.value = res.data.data;
    } catch (e) {}
};

// Client-side filtering for fast interactive search & tag filtering
const filteredArticles = computed(() => {
    return articles.value.filter((item) => {
        // Search filter
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase();
            const titleMatch = (item.bengali_title || '').toLowerCase().includes(q) ||
                (item.title || '').toLowerCase().includes(q) ||
                (item.channel_name || '').toLowerCase().includes(q);
            if (!titleMatch) return false;
        }

        // Tag filter
        if (selectedTag.value) {
            const hasTag = (item.tags || []).some((t) => t.slug === selectedTag.value || t.name === selectedTag.value);
            if (!hasTag) return false;
        }

        // Visibility filter
        if (selectedVisibility.value) {
            if (item.visibility !== selectedVisibility.value) return false;
        }

        return true;
    });
});

const toggleBookmark = async (article) => {
    try {
        const res = await api.post(`/user/articles/${article.id}/bookmark`);
        article.is_bookmarked = res.data.is_bookmarked;
        success(res.data.message);
        if (onlyBookmarked.value && !article.is_bookmarked) {
            articles.value = articles.value.filter((a) => a.id !== article.id);
        }
    } catch (e) {
        error('বুকমার্ক পরিবর্তন করা যায়নি।');
    }
};

const openArticle = (id) => {
    router.push(`/articles/${id}`);
};

const openVideoModal = (item) => {
    videoModalRef.value?.openModal(item);
};

watch([selectedType, onlyBookmarked], () => {
    fetchLibrary();
});

onMounted(() => {
    fetchLibrary();
    fetchTags();
});
</script>

<template>
    <div class="library-page py-4">
        <div class="container">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h2 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-journal-richtext text-primary me-2"></i>ব্যক্তিগত নলেজ লাইব্রেরি
                    </h2>
                    <p class="text-secondary small mb-0">
                        আপনার সংরক্ষিত সকল ইউটিউব ভিডিও ও বাংলা মেন্টর-স্টাইল আর্টিকেলের ভাণ্ডার।
                    </p>
                </div>
                <div class="d-flex gap-2 mt-3 mt-sm-0">
                    <button class="btn btn-primary btn-sm px-3" @click="addModalRef?.openModal()">
                        <i class="bi bi-plus-circle me-1"></i> নতুন ভিডিও যুক্ত করুন
                    </button>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="card border-0 shadow-sm p-3 mb-4 bg-white rounded-3">
                <div class="row g-2 align-items-center">
                    <!-- Search Input -->
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="form-control border-start-0"
                                placeholder="শিরোনাম বা চ্যানেল দিয়ে খুঁজুন..."
                            />
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="col-6 col-md-2">
                        <select v-model="selectedType" class="form-select form-select-sm">
                            <option value="">সব ফরম্যাট</option>
                            <option value="video">সাধারণ ভিডিও</option>
                            <option value="short">Shorts</option>
                        </select>
                    </div>

                    <!-- Visibility Filter -->
                    <div class="col-6 col-md-2">
                        <select v-model="selectedVisibility" class="form-select form-select-sm">
                            <option value="">সব ভিজিবিলিটি</option>
                            <option value="private">Private</option>
                            <option value="public">Public</option>
                        </select>
                    </div>

                    <!-- Bookmarks Toggle -->
                    <div class="col-md-3 text-md-end">
                        <button
                            type="button"
                            class="btn btn-sm"
                            :class="onlyBookmarked ? 'btn-warning text-dark' : 'btn-outline-secondary'"
                            @click="onlyBookmarked = !onlyBookmarked"
                        >
                            <i class="bi" :class="onlyBookmarked ? 'bi-bookmark-star-fill' : 'bi-bookmark'"></i>
                            {{ onlyBookmarked ? 'শুধু বুকমার্কড' : 'বুকমার্ক ফিল্টার' }}
                        </button>
                    </div>
                </div>

                <!-- Tag Filter Pills -->
                <div v-if="tags.length > 0" class="d-flex flex-wrap gap-1 align-items-center pt-3 border-top mt-3">
                    <span class="smaller text-muted me-1"><i class="bi bi-tags"></i> ট্যাগ:</span>
                    <button
                        type="button"
                        class="btn btn-sm py-0 px-2 rounded-pill smaller"
                        :class="!selectedTag ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="selectedTag = ''"
                    >
                        সকল
                    </button>
                    <button
                        v-for="t in tags"
                        :key="t.id"
                        type="button"
                        class="btn btn-sm py-0 px-2 rounded-pill smaller"
                        :class="selectedTag === t.slug ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="selectedTag = selectedTag === t.slug ? '' : t.slug"
                    >
                        #{{ t.name }} ({{ t.aggregated_contents_count || 0 }})
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div v-if="isLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">লাইব্রেরি লোড হচ্ছে...</p>
            </div>

            <div v-else-if="filteredArticles.length === 0" class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
                <i class="bi bi-collection text-muted fs-1 mb-2"></i>
                <h6 class="fw-bold text-dark">কোনো আর্টিকেল পাওয়া যায়নি</h6>
                <p class="text-secondary small mb-3">আপনার সার্চ ফিল্টার পরিবর্তন করুন অথবা নতুন ভিডিও যুক্ত করুন।</p>
                <div class="d-inline-block">
                    <button class="btn btn-primary btn-sm px-3" @click="addModalRef?.openModal()">
                        <i class="bi bi-plus-circle me-1"></i> নতুন ভিডিও যুক্ত করুন
                    </button>
                </div>
            </div>

            <!-- Card Grid -->
            <div v-else class="row g-3">
                <div v-for="item in filteredArticles" :key="item.id" class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-3 overflow-hidden d-flex flex-column hover-shadow transition">
                        <!-- Thumbnail Area -->
                        <div class="position-relative bg-light cursor-pointer" @click="openArticle(item.id)">
                            <img
                                :src="item.thumbnail_url || 'https://img.youtube.com/vi/' + item.youtube_video_id + '/hqdefault.jpg'"
                                alt="Thumbnail"
                                class="w-100 object-fit-cover"
                                style="height: 180px;"
                            />
                            <div class="position-absolute top-0 end-0 p-2 d-flex gap-1">
                                <span v-if="item.content_type === 'short'" class="badge bg-danger shadow-sm">
                                    <i class="bi bi-lightning-fill"></i> Shorts
                                </span>
                                <span v-if="item.visibility === 'public'" class="badge bg-success shadow-sm">
                                    <i class="bi bi-globe2"></i> Public
                                </span>
                            </div>
                            <div class="position-absolute bottom-0 start-0 p-2">
                                <span class="badge bg-dark bg-opacity-75">
                                    <i class="bi bi-clock me-1"></i> {{ item.reading_time_minutes }} মিনিট
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-1 mb-2">
                                <StatusBadge :status="item.status" />
                                <button
                                    class="btn btn-link btn-sm p-0 text-decoration-none"
                                    :title="item.is_bookmarked ? 'বুকমার্ক সরান' : 'বুকমার্ক করুন'"
                                    @click="toggleBookmark(item)"
                                >
                                    <i class="bi fs-5" :class="item.is_bookmarked ? 'bi-bookmark-star-fill text-warning' : 'bi-bookmark text-muted'"></i>
                                </button>
                            </div>

                            <h6
                                class="fw-bold text-dark mb-1 cursor-pointer line-clamp-2"
                                :title="item.bengali_title || item.title"
                                @click="openArticle(item.id)"
                            >
                                {{ item.bengali_title || item.title }}
                            </h6>

                            <div class="text-secondary smaller mb-2">
                                <i class="bi bi-person-video2 me-1"></i> {{ item.channel_name }}
                            </div>

                            <!-- Tags Pills -->
                            <div v-if="item.tags && item.tags.length > 0" class="d-flex flex-wrap gap-1 mt-auto pt-2">
                                <span v-for="t in item.tags.slice(0, 3)" :key="t.name" class="badge bg-light text-secondary border smaller">
                                    #{{ t.name }}
                                </span>
                                <span v-if="item.tags.length > 3" class="badge bg-light text-muted smaller">
                                    +{{ item.tags.length - 3 }}
                                </span>
                            </div>

                            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm py-1 px-2 smaller d-inline-flex align-items-center gap-1"
                                    title="ইন-অ্যাপ ভিডিও প্লেয়ার ও তথ্য দেখুন"
                                    @click.stop="openVideoModal(item)"
                                >
                                    <i class="bi bi-youtube"></i> ভিডিও
                                </button>

                                <div class="d-flex align-items-center gap-2">
                                    <span v-if="item.reading_progress?.is_completed" class="badge bg-success-subtle text-success smaller">
                                        <i class="bi bi-check2-circle"></i> সম্পন্ন
                                    </span>
                                    <button class="btn btn-primary btn-sm px-3" @click="openArticle(item.id)">
                                        <i class="bi bi-book-half me-1"></i> পড়ুন
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AddContentModal ref="addModalRef" @saved="fetchLibrary" />
        <VideoDetailsModal ref="videoModalRef" />
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.75rem;
}
.cursor-pointer {
    cursor: pointer;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12) !important;
    transform: translateY(-2px);
}
.transition {
    transition: all 0.2s ease-in-out;
}
</style>

