<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useWatchHistory } from '@/composables/useWatchHistory';
import { useConfirm } from '@/composables/useConfirm';
import VideoDetailsModal from '@/components/video/VideoDetailsModal.vue';

const router = useRouter();
const { confirm } = useConfirm();

const {
    items,
    channels,
    pagination,
    latestImport,
    serverFileInfo,
    isUploading,
    isLoadingItems,
    isImporting,
    checkServerFile,
    loadServerFile,
    uploadFile,
    fetchItems,
    fetchChannels,
    batchImport,
} = useWatchHistory();

const videoModalRef = ref(null);
const fileInputRef = ref(null);

// Filters State
const searchQuery = ref('');
const selectedChannel = ref('');
const selectedStatus = ref('not_saved'); // 'all' | 'not_saved' | 'saved'
const selectedContentType = ref(''); // '' | 'video' | 'short'
const currentPage = ref(1);

// Selected item IDs for batch import
const selectedIds = ref([]);
const showHowToGuide = ref(false);

const loadData = async (page = 1) => {
    currentPage.value = page;
    const params = {
        page,
        per_page: 25,
    };
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    if (selectedChannel.value) params.channel = selectedChannel.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedContentType.value) params.content_type = selectedContentType.value;
    if (latestImport.value?.import_id) params.import_id = latestImport.value.import_id;

    await fetchItems(params);
};

// Filter watchers
watch([selectedChannel, selectedStatus, selectedContentType], () => {
    loadData(1);
});

let debounceTimer = null;
const handleSearchInput = () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadData(1);
    }, 400);
};

// File Upload Handler
const handleFileUpload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    try {
        await uploadFile(file);
        selectedIds.value = [];
        selectedChannel.value = '';
    } catch (e) {
        // Handled in composable
    } finally {
        if (fileInputRef.value) fileInputRef.value.value = '';
    }
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

// Selection Helpers
const toggleSelectAllPage = () => {
    const selectable = items.value.filter((i) => !i.is_aggregated);
    const allSelected = selectable.every((i) => selectedIds.value.includes(i.id));

    if (allSelected) {
        // Deselect current page selectable items
        const pageIds = selectable.map((i) => i.id);
        selectedIds.value = selectedIds.value.filter((id) => !pageIds.includes(id));
    } else {
        // Select all current page selectable items
        const newIds = selectable.map((i) => i.id).filter((id) => !selectedIds.value.includes(id));
        selectedIds.value = [...selectedIds.value, ...newIds];
    }
};

const isItemSelected = (id) => {
    return selectedIds.value.includes(id);
};

const toggleItemSelection = (id) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((i) => i !== id);
    } else {
        selectedIds.value.push(id);
    }
};

const selectAllNewOnPage = () => {
    const newItems = items.value.filter((i) => !i.is_aggregated);
    const newIds = newItems.map((i) => i.id).filter((id) => !selectedIds.value.includes(id));
    selectedIds.value = [...selectedIds.value, ...newIds];
};

const clearSelection = () => {
    selectedIds.value = [];
};

// Batch Import Submission
const handleBatchImport = async () => {
    if (selectedIds.value.length === 0) return;

    const count = selectedIds.value.length;
    const isConfirmed = await confirm(`আপনি কি নির্বাচিত ${count} টি ভিডিও এআই দিয়ে বাংলায় রূপান্তর করতে চান?`, { title: 'ব্যাচ এআই রূপান্তর শুরু করবেন?', variant: 'primary', icon: 'bi-stars' });
    if (!isConfirmed) return;

    try {
        await batchImport(selectedIds.value);
        selectedIds.value = [];
        await loadData(currentPage.value);
        // Prompt user to view dashboard
        if (await confirm('ভিডিওগুলো ব্যাকগ্রাউন্ডে প্রসেস শুরু হয়েছে! আপনি কি এখনই ড্যাশবোর্ডে গিয়ে প্রোগ্রেস দেখতে চান?', { title: 'প্রসেসিং শুরু হয়েছে! 🚀', variant: 'primary', icon: 'bi-speedometer2' })) {
            router.push('/dashboard');
        }
    } catch (e) {}
};

const openVideoModal = (item) => {
    videoModalRef.value?.openModal(item);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('bn-BD', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch (e) {
        return dateStr;
    }
};

// Server File Direct Load Handler
const handleLoadServerFile = async (fileName) => {
    try {
        await loadServerFile(fileName);
        selectedIds.value = [];
        selectedChannel.value = '';
    } catch (e) {
        // Handled in composable
    }
};

onMounted(async () => {
    await checkServerFile();
    await fetchChannels();
    await loadData(1);
});
</script>

<template>
    <div class="watch-history-page bg-light min-vh-100 pb-5">
        <div class="container py-4">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill fw-semibold">
                            <i class="bi bi-youtube me-1"></i> গুগল টেকআউট
                        </span>
                    </div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="bi bi-clock-history text-primary me-2"></i>ইউটিউব হিস্ট্রি বাল্ক কিউরেশন
                    </h2>
                    <p class="text-secondary small mb-0">
                        আপনার পুরো ইউটিউব দেখার হিস্ট্রি একবারে আপলোড করে শুধুমাত্র শিক্ষণীয় ভিডিওগুলো বেছে নিয়ে বাংলায় রূপান্তর করুন।
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2 mt-3 mt-sm-0">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                        @click="showHowToGuide = !showHowToGuide"
                    >
                        <i class="bi bi-question-circle me-1"></i> কীভাবে ফাইল পাবেন?
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1"
                        :disabled="isUploading"
                        @click="triggerFileInput"
                    >
                        <i v-if="!isUploading" class="bi bi-cloud-arrow-up"></i>
                        <span v-else class="spinner-border spinner-border-sm" role="status"></span>
                        <span>নতুন হিস্ট্রি ফাইল আপলোড</span>
                    </button>
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept=".json,.html"
                        class="d-none"
                        @change="handleFileUpload"
                    />
                </div>
            </div>

            <!-- How To Get Google Takeout Guide Accordion -->
            <div v-if="showHowToGuide" class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 transition-all">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>Google Takeout থেকে ইউটিউব হিস্ট্রি পাওয়ার সহজ ৪টি ধাপ:
                    </h5>
                    <button type="button" class="btn-close" @click="showHowToGuide = false"></button>
                </div>
                <div class="row g-3 small text-secondary">
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-3 h-100 border">
                            <span class="badge bg-primary rounded-circle mb-2">১</span>
                            <div class="fw-bold text-dark mb-1">গুগল টেকআউটে যান</div>
                            <div>ব্রাউজারে <strong>takeout.google.com</strong> খুলুন এবং আপনার গুগল অ্যাকাউন্টে লগইন করুন।</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-3 h-100 border">
                            <span class="badge bg-primary rounded-circle mb-2">২</span>
                            <div class="fw-bold text-dark mb-1">শুধুমাত্র YouTube নির্বাচন করুন</div>
                            <div>উপরে "Deselect all"-এ ক্লিক করে নিচে গিয়ে শুধুমাত্র "YouTube and YouTube Music" টিক দিন।</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-3 h-100 border">
                            <span class="badge bg-primary rounded-circle mb-2">৩</span>
                            <div class="fw-bold text-dark mb-1">হিস্ট্রি সিলেক্ট করুন</div>
                            <div>"All YouTube data included"-এ ক্লিক করে শুধুমাত্র <strong>"history"</strong> চেক করুন এবং Format দিন <strong>JSON</strong>।</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-light rounded-3 h-100 border">
                            <span class="badge bg-primary rounded-circle mb-2">৪</span>
                            <div class="fw-bold text-dark mb-1">ফাইল আপলোড করুন</div>
                            <div>ডাউনলোড করা জিপ ফাইলের ভেতরের <code>watch-history.json</code> ফাইলটি এখানে ড্র্যাগ করে দিন!</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Local Server File Found Banner -->
            <div v-if="serverFileInfo" class="card border-0 shadow-sm rounded-4 p-4 bg-primary-subtle border-start border-primary border-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center shadow-xs">
                            <i class="bi bi-file-earmark-code fs-3"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 fw-bold smaller">
                                    <i class="bi bi-lightning-fill"></i> ইনস্ট্যান্ট প্রসেসিং
                                </span>
                                <h5 class="fw-bold text-dark mb-0">
                                    পাবলিক ফোল্ডারে ফাইল পাওয়া গেছে!
                                </h5>
                            </div>
                            <div class="small text-secondary">
                                <span class="badge bg-white text-primary border fw-semibold me-2">{{ serverFileInfo.file_name }}</span>
                                <span>ফাইলের আকার: <strong>{{ serverFileInfo.file_size_formatted }}</strong></span>
                            </div>
                            <div class="smaller text-muted mt-1">
                                ব্রাউজার দিয়ে ভারী ফাইল আপলোডের ঝামেলা ছাড়াই সরাসরি প্রজেক্টের public ফোল্ডার থেকে কয়েক সেকেন্ডে লোড করুন।
                            </div>
                        </div>
                    </div>
                    <div>
                        <button
                            type="button"
                            class="btn btn-primary px-4 py-2.5 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2"
                            :disabled="isUploading"
                            @click="() => handleLoadServerFile(serverFileInfo.file_name)"
                        >
                            <i v-if="!isUploading" class="bi bi-box-arrow-in-down-right"></i>
                            <span v-else class="spinner-border spinner-border-sm" role="status"></span>
                            <span>{{ isUploading ? 'বিশ্লেষণ চলছে...' : 'সরাসরি এই ফাইলটি লোড করুন' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upload Area (Shown prominently when no items yet) -->
            <div
                v-if="items.length === 0 && !isLoadingItems && !searchQuery && !selectedChannel"
                class="card border-2 border-dashed shadow-sm rounded-4 text-center p-5 bg-white mb-4 cursor-pointer hover-bg-light transition-all"
                @click="triggerFileInput"
            >
                <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 72px; height: 72px;">
                    <i class="bi bi-cloud-arrow-up fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">আপনার watch-history.json বা .html ফাইল আপলোড করুন</h4>
                <p class="text-secondary small max-w-500 mx-auto mb-4">
                    গুগল টেকআউট থেকে পাওয়া ইউটিউব হিস্ট্রি ফাইল ড্রপ করুন। আমরা কয়েক সেকেন্ডের মধ্যে আপনার শত শত ভিডিওর লিস্ট তৈরি করে দেব।
                </p>
                <div>
                    <button type="button" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm" :disabled="isUploading">
                        <i v-if="!isUploading" class="bi bi-folder2-open me-2"></i>
                        <span v-else class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <span>{{ isUploading ? 'ফাইল বিশ্লেষণ চলছে...' : 'কম্পিউটার থেকে ফাইল বেছে নিন' }}</span>
                    </button>
                </div>
            </div>

            <!-- Curation Content Area (When Items Exist) -->
            <div v-else>
                <!-- Channels Filter Pills -->
                <div v-if="channels.length > 0" class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-secondary">
                            <i class="bi bi-person-video2 text-primary me-1"></i> শীর্ষ চ্যানেল অনুযায়ী দ্রুত ফিল্টার:
                        </span>
                        <button
                            v-if="selectedChannel"
                            type="button"
                            class="btn btn-link btn-sm text-danger text-decoration-none p-0 smaller fw-semibold"
                            @click="selectedChannel = ''"
                        >
                            <i class="bi bi-x-circle me-1"></i> চ্যানেল ফিল্টার মুছুন
                        </button>
                    </div>

                    <div class="d-flex flex-wrap gap-1">
                        <button
                            type="button"
                            class="btn btn-sm py-1 px-3 rounded-pill smaller fw-medium transition-all"
                            :class="!selectedChannel ? 'btn-primary text-white' : 'btn-light text-secondary border'"
                            @click="selectedChannel = ''"
                        >
                            সব চ্যানেল
                        </button>
                        <button
                            v-for="ch in channels.slice(0, 15)"
                            :key="ch.channel_name"
                            type="button"
                            class="btn btn-sm py-1 px-3 rounded-pill smaller fw-medium transition-all"
                            :class="selectedChannel === ch.channel_name ? 'btn-primary text-white' : 'btn-light text-secondary border'"
                            @click="selectedChannel = selectedChannel === ch.channel_name ? '' : ch.channel_name"
                        >
                            {{ ch.channel_name }}
                            <span class="badge bg-white bg-opacity-25 ms-1 text-dark">{{ ch.count }}</span>
                        </button>
                    </div>
                </div>

                <!-- Filter Controls & Search Bar -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3">
                    <div class="row g-2 align-items-center">
                        <!-- Search Box -->
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control border-start-0"
                                    placeholder="ভিডিও টাইটেল বা চ্যানেলের নাম খুঁজুন..."
                                    @input="handleSearchInput"
                                />
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-6 col-md-3">
                            <select v-model="selectedStatus" class="form-select form-select-sm">
                                <option value="not_saved">শুধুমাত্র নতুন (অসংরক্ষিত)</option>
                                <option value="saved">ইতিমধ্যে লাইব্রেরিতে সংরক্ষিত</option>
                                <option value="all">সকল ভিডিও (সব স্ট্যাটাস)</option>
                            </select>
                        </div>

                        <!-- Content Type Filter -->
                        <div class="col-6 col-md-2">
                            <select v-model="selectedContentType" class="form-select form-select-sm">
                                <option value="">সব ফরম্যাট</option>
                                <option value="video">শুধুমাত্র ভিডিও (16:9)</option>
                                <option value="short">শুধুমাত্র শর্টস (Shorts)</option>
                            </select>
                        </div>

                        <!-- Selection Quick Actions -->
                        <div class="col-12 col-md-2 text-md-end">
                            <div class="btn-group btn-group-sm w-100">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary smaller"
                                    title="বর্তমান পেজের সব নতুন ভিডিও সিলেক্ট করুন"
                                    @click="selectAllNewOnPage"
                                >
                                    সব নির্বাচন
                                </button>
                                <button
                                    v-if="selectedIds.length > 0"
                                    type="button"
                                    class="btn btn-outline-secondary smaller"
                                    title="নির্বাচন বাতিল করুন"
                                    @click="clearSelection"
                                >
                                    মুছুন ({{ selectedIds.length }})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoadingItems" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="small text-muted mt-2">হিস্ট্রি তালিকা লোড হচ্ছে...</p>
                </div>

                <!-- Empty State after filtering -->
                <div v-else-if="items.length === 0" class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
                    <i class="bi bi-funnel text-muted fs-1 mb-2"></i>
                    <h5 class="fw-bold text-dark">কোনো ভিডিও পাওয়া যায়নি</h5>
                    <p class="text-secondary small mb-3">
                        আপনার সার্চ ফিল্টার পরিবর্তন করুন অথবা নতুন টেকআউট ফাইল আপলোড করুন।
                    </p>
                    <div>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill" @click="selectedChannel = ''; searchQuery = ''; selectedStatus = 'all'">
                            সব ফিল্টার রিসেট করুন
                        </button>
                    </div>
                </div>

                <!-- Video Items Table -->
                <div v-else class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="ps-3">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            :checked="items.some(i => !i.is_aggregated) && items.filter(i => !i.is_aggregated).every(i => isItemSelected(i.id))"
                                            @change="toggleSelectAllPage"
                                        />
                                    </th>
                                    <th style="width: 80px;">ভিডিও</th>
                                    <th>শিরোনাম ও মূল চ্যানেল</th>
                                    <th>ফরম্যাট</th>
                                    <th>দেখার তারিখ</th>
                                    <th>লাইব্রেরি স্ট্যাটাস</th>
                                    <th class="text-end pe-3">প্রিভিউ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in items"
                                    :key="item.id"
                                    :class="{ 'table-primary bg-opacity-10': isItemSelected(item.id) }"
                                >
                                    <!-- Checkbox -->
                                    <td class="ps-3">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            :checked="isItemSelected(item.id)"
                                            :disabled="item.is_aggregated"
                                            @change="toggleItemSelection(item.id)"
                                        />
                                    </td>

                                    <!-- Thumbnail -->
                                    <td>
                                        <div
                                            class="position-relative cursor-pointer rounded overflow-hidden shadow-xs"
                                            style="width: 72px; height: 42px;"
                                            title="ইন-অ্যাপ ভিডিও দেখুন"
                                            @click="openVideoModal(item)"
                                        >
                                            <img
                                                :src="`https://img.youtube.com/vi/${item.youtube_video_id}/default.jpg`"
                                                alt="Thumbnail"
                                                class="w-100 h-100 object-fit-cover"
                                            />
                                        </div>
                                    </td>

                                    <!-- Title & Channel -->
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 380px;" :title="item.title">
                                            {{ item.title }}
                                        </div>
                                        <div class="small text-muted d-flex align-items-center gap-1 mt-0.5">
                                            <i class="bi bi-person-fill text-primary"></i>
                                            <span class="cursor-pointer text-hover-primary" @click="selectedChannel = item.channel_name">
                                                {{ item.channel_name }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Format -->
                                    <td>
                                        <span v-if="item.content_type === 'short'" class="badge bg-danger-subtle text-danger smaller">
                                            <i class="bi bi-lightning-fill"></i> Short
                                        </span>
                                        <span v-else class="badge bg-light text-secondary border smaller">
                                            <i class="bi bi-play-circle"></i> Video
                                        </span>
                                    </td>

                                    <!-- Watched Date -->
                                    <td class="small text-muted">
                                        {{ formatDate(item.watched_at) }}
                                    </td>

                                    <!-- Status -->
                                    <td>
                                        <span v-if="item.is_aggregated" class="badge bg-success-subtle text-success smaller border border-success-subtle">
                                            <i class="bi bi-check-circle-fill me-1"></i> লাইব্রেরিতে সংরক্ষিত
                                        </span>
                                        <span v-else class="badge bg-secondary-subtle text-secondary smaller">
                                            নতুন
                                        </span>
                                    </td>

                                    <!-- Preview Action -->
                                    <td class="text-end pe-3">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm py-0 px-2 smaller d-inline-flex align-items-center gap-1"
                                                title="ইন-অ্যাপ ভিডিও প্লেয়ার ও তথ্য দেখুন"
                                                @click="openVideoModal(item)"
                                            >
                                                <i class="bi bi-youtube"></i> ভিডিও
                                            </button>
                                            <router-link
                                                v-if="item.aggregated_content_id"
                                                :to="`/articles/${item.aggregated_content_id}`"
                                                class="btn btn-outline-primary btn-sm py-0 px-2 smaller"
                                                title="বাংলা আর্টিকেল পড়ুন"
                                            >
                                                পড়ুন
                                            </router-link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div
                        v-if="pagination.last_page > 1"
                        class="card-footer bg-white border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2"
                    >
                        <div class="small text-muted">
                            মোট <strong>{{ pagination.total }}</strong> টির মধ্যে 
                            <strong>{{ (pagination.current_page - 1) * pagination.per_page + 1 }} - {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</strong> টি দেখানো হচ্ছে
                        </div>

                        <div class="btn-group btn-group-sm">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                :disabled="pagination.current_page <= 1"
                                @click="loadData(pagination.current_page - 1)"
                            >
                                <i class="bi bi-chevron-left me-1"></i> পূর্ববর্তী
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-secondary disabled"
                            >
                                পৃষ্ঠা {{ pagination.current_page }} / {{ pagination.last_page }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                :disabled="pagination.current_page >= pagination.last_page"
                                @click="loadData(pagination.current_page + 1)"
                            >
                                পরবর্তী <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Floating Ingestion Action Bar -->
        <div
            v-if="selectedIds.length > 0"
            class="sticky-import-bar position-fixed bottom-0 start-50 translate-middle-x mb-4 shadow-lg rounded-pill p-2 px-4 bg-dark text-white d-flex align-items-center gap-3 z-3 border border-secondary"
        >
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill px-2 py-1 fs-6">
                    {{ selectedIds.length }}
                </span>
                <span class="small fw-semibold">টি ভিডিও নির্বাচিত হয়েছে</span>
            </div>

            <button
                type="button"
                class="btn btn-light btn-sm text-dark px-3 py-1 rounded-pill smaller fw-semibold"
                @click="clearSelection"
            >
                বাতিল করুন
            </button>

            <button
                type="button"
                class="btn btn-primary btn-sm px-4 py-2 rounded-pill fw-bold shadow-sm d-inline-flex align-items-center gap-2"
                :disabled="isImporting"
                @click="handleBatchImport"
            >
                <span v-if="isImporting" class="spinner-border spinner-border-sm" role="status"></span>
                <i v-else class="bi bi-stars"></i>
                <span>এআই দিয়ে বাংলায় রূপান্তর শুরু করুন</span>
            </button>
        </div>

        <!-- In-App Video Details Modal -->
        <VideoDetailsModal ref="videoModalRef" />
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.78rem;
}

.shadow-xs {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.max-w-500 {
    max-width: 500px;
}

.cursor-pointer {
    cursor: pointer;
}

.text-hover-primary:hover {
    color: var(--brand-primary) !important;
    text-decoration: underline;
}

.hover-bg-light:hover {
    background-color: var(--surface-sunken) !important;
    border-color: var(--brand-primary) !important;
}

.sticky-import-bar {
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translate(-50%, 100%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, 0);
        opacity: 1;
    }
}
</style>

