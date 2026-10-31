<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { useContent } from '@/composables/useContent';
import { useNotification } from '@/composables/useNotification';
import { useTour } from '@/composables/useTour';
import AddContentModal from '@/components/content/AddContentModal.vue';
import ManualTranscriptModal from '@/components/content/ManualTranscriptModal.vue';
import StatusBadge from '@/components/content/StatusBadge.vue';
import VideoDetailsModal from '@/components/video/VideoDetailsModal.vue';

const { user } = useAuth();
const { articles, isLoading, fetchArticles, retryContent, deleteContent } = useContent();
const { success, error } = useNotification();
const { openTour, shouldAutoShow } = useTour();

const addModalRef = ref(null);
const manualTranscriptModalRef = ref(null);
const videoModalRef = ref(null);
let pollInterval = null;

const openAddModal = () => {
    addModalRef.value?.openModal();
};

const openVideoModal = (item) => {
    videoModalRef.value?.openModal(item);
};

const openManualTranscriptModal = (article) => {
    manualTranscriptModalRef.value?.openModal(article);
};

const handleRetry = async (article) => {
    try {
        await retryContent(article.id);
        success('পুনরায় প্রসেসিং শুরু হয়েছে।');
    } catch (err) {
        error(err.response?.data?.message || 'পুনরায় প্রসেসিং শুরু করতে ব্যর্থ হয়েছে।');
    }
};

const handleDelete = async (article) => {
    if (!confirm('আপনি কি নিশ্চিত যে এই কনটেন্টটি মুছে ফেলতে চান?')) return;
    try {
        await deleteContent(article.id);
        success('কনটেন্ট সফলভাবে মুছে ফেলা হয়েছে।');
    } catch (err) {
        error('মুছে ফেলতে সমস্যা হয়েছে।');
    }
};

// Polling for articles that are currently processing
const hasProcessingArticles = computed(() => {
    return articles.value.some((a) => !['completed', 'failed'].includes(a.status));
});

const startPolling = () => {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(async () => {
        if (hasProcessingArticles.value) {
            await fetchArticles();
        }
    }, 4000);
};

const checkAndTriggerTour = () => {
    if (user.value?.id && shouldAutoShow(user.value.id)) {
        setTimeout(() => {
            openTour(0);
        }, 700);
    }
};

watch(user, (newUser) => {
    if (newUser?.id) {
        checkAndTriggerTour();
    }
});

onMounted(async () => {
    await fetchArticles();
    startPolling();
    checkAndTriggerTour();
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

// Dynamic summary numbers
const completedCount = computed(() => articles.value.filter((a) => a.status === 'completed').length);
const totalMinutes = computed(() => {
    return articles.value
        .filter((a) => a.status === 'completed')
        .reduce((sum, a) => sum + (a.reading_time_minutes || 1), 0);
});
</script>

<template>
    <div class="dashboard-page py-4">
        <div class="container">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h2 class="fw-bold mb-1">
                        স্বাগতম, <span class="text-primary">{{ user?.name || 'লার্নার' }}</span>! 👋
                    </h2>
                    <p class="text-secondary mb-0 small">
                        আপনার সোশ্যাল মিডিয়া লার্নিং ড্যাশবোর্ড ও সেকেন্ড ব্রেন লাইব্রেরি।
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2 mt-3 mt-sm-0">
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-xs"
                        title="প্ল্যাটফর্মের সকল ফিচার পরিচিতি দেখুন"
                        @click="() => openTour(0)"
                    >
                        <i class="bi bi-compass"></i>
                        <span>প্ল্যাটফর্ম ট্যুর</span>
                    </button>
                    <button class="btn btn-primary btn-sm rounded-pill px-3 py-1.5 shadow-xs" @click="openAddModal">
                        <i class="bi bi-plus-circle me-1"></i> নতুন ইউটিউব ভিডিও যুক্ত করুন
                    </button>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">সংরক্ষিত আর্টিকেল</span>
                                <h3 class="fw-bold text-dark mt-1 mb-0">{{ articles.length }}</h3>
                            </div>
                            <div class="p-3 bg-primary-subtle text-primary rounded-3">
                                <i class="bi bi-journal-richtext fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">প্রস্তুত সম্পন্ন</span>
                                <h3 class="fw-bold text-success mt-1 mb-0">{{ completedCount }}</h3>
                            </div>
                            <div class="p-3 bg-success-subtle text-success rounded-3">
                                <i class="bi bi-check2-all fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">মোট পড়ার সময়</span>
                                <h3 class="fw-bold text-dark mt-1 mb-0">{{ totalMinutes }} মিনিট</h3>
                            </div>
                            <div class="p-3 bg-info-subtle text-info rounded-3">
                                <i class="bi bi-clock-history fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold">লার্নিং স্টাডি স্ট্রিক</span>
                                <h3 class="fw-bold text-warning mt-1 mb-0">১ দিন 🔥</h3>
                            </div>
                            <div class="p-3 bg-warning-subtle text-warning rounded-3">
                                <i class="bi bi-fire fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-collection-play me-2 text-primary"></i>সংরক্ষিত ভিডিও ও বাংলা জ্ঞানভাণ্ডার
                    </h5>
                    <button class="btn btn-outline-secondary btn-sm" :disabled="isLoading" @click="fetchArticles">
                        <i class="bi bi-arrow-clockwise me-1" :class="{ 'spin': isLoading }"></i> রিফ্রেশ
                    </button>
                </div>

                <div class="card-body p-0">
                    <!-- Loading State -->
                    <div v-if="isLoading && articles.length === 0" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="small text-muted mt-2">লাইব্রেরি লোড হচ্ছে...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="articles.length === 0" class="text-center py-5 px-3">
                        <div class="mb-3 text-muted">
                            <i class="bi bi-inbox fs-1"></i>
                        </div>
                        <h6 class="fw-bold text-dark">এখনো কোনো ভিডিও কনটেন্ট যুক্ত করা হয়নি</h6>
                        <p class="text-secondary small max-w-400 mx-auto mb-3">
                            যেকোনো ইউটিউব ভিডিও বা শর্টসের লিঙ্ক পেস্ট করে আপনার প্রথম বাংলা মেন্টর-স্টাইল আর্টিকেল রূপান্তর করুন।
                        </p>
                        <button class="btn btn-primary btn-sm px-4" @click="openAddModal">
                            <i class="bi bi-plus-circle me-1"></i> প্রথম ভিডিও যুক্ত করুন
                        </button>
                    </div>

                    <!-- Articles Table / Cards -->
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">ভিডিও</th>
                                    <th>শিরোনাম ও মূল উৎস</th>
                                    <th>বাংলা শিরোনাম</th>
                                    <th>ফরম্যাট</th>
                                    <th>স্ট্যাটাস</th>
                                    <th class="text-end pe-3">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in articles" :key="item.id">
                                    <td>
                                        <div
                                            class="position-relative cursor-pointer d-inline-block rounded overflow-hidden"
                                            title="ভিডিও প্লেয়ার ও বিস্তারিত দেখুন"
                                            @click="openVideoModal(item)"
                                        >
                                            <img
                                                :src="item.thumbnail_url || 'https://img.youtube.com/vi/' + item.youtube_video_id + '/default.jpg'"
                                                alt="Thumbnail"
                                                class="rounded object-fit-cover shadow-sm"
                                                style="width: 72px; height: 42px;"
                                            />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 280px;" :title="item.title">
                                            {{ item.title }}
                                        </div>
                                        <div class="small text-muted d-flex align-items-center gap-1">
                                            <i class="bi bi-person-video2"></i> {{ item.channel_name }}
                                        </div>
                                    </td>
                                    <td>
                                        <div v-if="item.bengali_title" class="fw-medium text-primary text-truncate cursor-pointer" style="max-width: 260px;" :title="item.bengali_title" @click="openVideoModal(item)">
                                            {{ item.bengali_title }}
                                        </div>
                                        <span v-else class="text-muted smaller fst-italic">প্রসেসিং চলছে...</span>
                                    </td>
                                    <td>
                                        <span v-if="item.content_type === 'short'" class="badge bg-danger-subtle text-danger">
                                            <i class="bi bi-lightning-fill"></i> Short
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border">
                                            <i class="bi bi-play-circle"></i> Video
                                        </span>
                                    </td>
                                    <td>
                                        <StatusBadge :status="item.status" />
                                        <div v-if="item.error_message" class="text-danger smaller mt-1 text-truncate" style="max-width: 180px;" :title="item.error_message">
                                            {{ item.error_message }}
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Video Modal Button -->
                                            <button
                                                class="btn btn-outline-danger"
                                                title="ভিডিও প্লেয়ার ও বিস্তারিত তথ্য"
                                                @click="openVideoModal(item)"
                                            >
                                                <i class="bi bi-youtube"></i>
                                            </button>

                                            <!-- Read Article Button if completed -->
                                            <router-link
                                                v-if="item.status === 'completed'"
                                                :to="`/articles/${item.id}`"
                                                class="btn btn-outline-primary"
                                                title="বাংলা আর্টিকেল পড়ুন"
                                            >
                                                <i class="bi bi-book-half"></i>
                                            </router-link>

                                            <!-- Manual Transcript Button if failed -->
                                            <button
                                                v-if="item.status === 'failed' && !item.has_transcript"
                                                class="btn btn-outline-warning"
                                                title="ম্যানুয়াল ট্রান্সক্রিপ্ট দিন"
                                                @click="openManualTranscriptModal(item)"
                                            >
                                                <i class="bi bi-file-earmark-text"></i>
                                            </button>

                                            <!-- Retry Button if failed -->
                                            <button
                                                v-if="item.status === 'failed'"
                                                class="btn btn-outline-primary"
                                                title="আবার চেষ্টা করুন"
                                                @click="handleRetry(item)"
                                            >
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button
                                                class="btn btn-outline-danger"
                                                title="মুছে ফেলুন"
                                                @click="handleDelete(item)"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ingestion, Manual Transcript & Video Details Modals -->
        <AddContentModal ref="addModalRef" @saved="fetchArticles" />
        <ManualTranscriptModal ref="manualTranscriptModalRef" @updated="fetchArticles" />
        <VideoDetailsModal ref="videoModalRef" />
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.75rem;
}
.max-w-400 {
    max-width: 400px;
}
.spin {
    animation: icon-spin 1s linear infinite;
}
@keyframes icon-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
