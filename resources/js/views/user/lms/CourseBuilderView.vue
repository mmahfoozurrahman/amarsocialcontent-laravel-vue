<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useLms } from '@/composables/useLms';
import { useNotification } from '@/composables/useNotification';
import { useConfirm } from '@/composables/useConfirm';

const route = useRoute();
const router = useRouter();
const courseId = computed(() => Number(route.params.id));

const {
    currentCourse,
    unassignedArticles,
    isLoading,
    isSaving,
    fetchCourse,
    createChapter,
    deleteChapter,
    createLesson,
    deleteLesson,
    fetchUnassignedArticles,
} = useLms();

const { success, error } = useNotification();
const { confirm } = useConfirm();

// Modal States
const showChapterModal = ref(false);
const chapterTitle = ref('');
const chapterDesc = ref('');

const showLessonModal = ref(false);
const activeChapterId = ref(null);
const lessonTitle = ref('');
const lessonContent = ref('');
const selectedArticleId = ref('');

// Open Add Chapter
const openAddChapter = () => {
    chapterTitle.value = '';
    chapterDesc.value = '';
    showChapterModal.value = true;
};

// Handle Add Chapter
const handleAddChapter = async () => {
    if (!chapterTitle.value.trim()) return;
    try {
        await createChapter(courseId.value, {
            title: chapterTitle.value.trim(),
            description: chapterDesc.value.trim(),
        });
        success('চ্যাপ্টার সফলভাবে তৈরি হয়েছে।');
        showChapterModal.value = false;
    } catch (err) {
        error('চ্যাপ্টার তৈরিতে সমস্যা হয়েছে।');
    }
};

// Open Add Lesson
const openAddLesson = (chapterId, preselectedArticle = null) => {
    activeChapterId.value = chapterId;
    if (preselectedArticle) {
        lessonTitle.value = preselectedArticle.bengali_title || preselectedArticle.title;
        selectedArticleId.value = preselectedArticle.id;
    } else {
        lessonTitle.value = '';
        selectedArticleId.value = '';
    }
    lessonContent.value = '';
    showLessonModal.value = true;
};

// Handle Add Lesson
const handleAddLesson = async () => {
    if (!lessonTitle.value.trim()) return;
    try {
        await createLesson(courseId.value, activeChapterId.value, {
            title: lessonTitle.value.trim(),
            aggregated_content_id: selectedArticleId.value ? Number(selectedArticleId.value) : null,
            content: lessonContent.value.trim() || null,
        });
        success('লেসন সফলভাবে যুক্ত হয়েছে।');
        showLessonModal.value = false;
        fetchUnassignedArticles();
    } catch (err) {
        error(err.response?.data?.message || 'লেসন তৈরিতে সমস্যা হয়েছে।');
    }
};

// Delete Chapter
const handleDeleteChapter = async (chapter) => {
    const isConfirmed = await confirm({
        title: 'চ্যাপ্টার মুছে ফেলুন',
        message: `"${chapter.title}" চ্যাপ্টারটি এবং এর ভেতরের সকল লেসন স্থায়ীভাবে মুছে যাবে।`,
        confirmLabel: 'হ্যাঁ, চ্যাপ্টার মুছুন',
        cancelLabel: 'বাতিল',
    });

    if (!isConfirmed) return;

    try {
        await deleteChapter(courseId.value, chapter.id);
        success('চ্যাপ্টার মুছে ফেলা হয়েছে।');
        fetchUnassignedArticles();
    } catch (e) {
        error('চ্যাপ্টার মুছতে সমস্যা হয়েছে।');
    }
};

// Delete Lesson
const handleDeleteLesson = async (lesson) => {
    const isConfirmed = await confirm({
        title: 'লেসন মুছে ফেলুন',
        message: `"${lesson.title}" লেসনটি কোর্স থেকে স্থায়ীভাবে মুছে যাবে। মূল আর্টিকেলটি আপনার লাইব্রেরিতে অক্ষত থাকবে।`,
        confirmLabel: 'হ্যাঁ, লেসন মুছুন',
        cancelLabel: 'বাতিল',
    });

    if (!isConfirmed) return;

    try {
        await deleteLesson(courseId.value, lesson.id);
        success('লেসন মুছে ফেলা হয়েছে।');
        fetchUnassignedArticles();
    } catch (e) {
        error('লেসন মুছতে সমস্যা হয়েছে।');
    }
};

onMounted(async () => {
    await fetchCourse(courseId.value);
    await fetchUnassignedArticles();
});
</script>

<template>
    <div class="course-builder-page py-4">
        <div class="container">
            <!-- Loading -->
            <div v-if="isLoading && !currentCourse" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">কোর্স লোড হচ্ছে...</p>
            </div>

            <div v-else-if="currentCourse" class="row g-4">
                <!-- Left: Course Structure & Chapter Accordions -->
                <div class="col-lg-8">
                    <!-- Header -->
                    <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <router-link to="/courses" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> সকল কোর্সে ফিরে যান
                            </router-link>
                            <span class="badge" :class="currentCourse.visibility === 'public' ? 'bg-success-subtle text-success' : 'bg-light text-secondary border'">
                                {{ currentCourse.visibility === 'public' ? 'Public' : 'Private' }}
                            </span>
                        </div>

                        <h3 class="fw-bold text-dark mt-2 mb-1">{{ currentCourse.title }}</h3>
                        <p class="text-secondary small mb-3">{{ currentCourse.description || 'কোনো বিবরণ দেওয়া হয়নি।' }}</p>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm px-3" @click="openAddChapter">
                                <i class="bi bi-plus-circle me-1"></i> নতুন চ্যাপ্টার যুক্ত করুন
                            </button>
                        </div>
                    </div>

                    <!-- Chapters Listing -->
                    <div v-if="!currentCourse.chapters || currentCourse.chapters.length === 0" class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
                        <i class="bi bi-folder-plus text-muted fs-1 mb-2"></i>
                        <h6 class="fw-bold text-dark">এই কোর্সে কোনো চ্যাপ্টার নেই</h6>
                        <p class="text-secondary small mb-3">কোর্সের জ্ঞান সাজাতে চ্যাপ্টার তৈরি করে তার মধ্যে লেসন ও আর্টিকেল যুক্ত করুন।</p>
                        <div>
                            <button class="btn btn-outline-primary btn-sm px-3" @click="openAddChapter">
                                <i class="bi bi-plus-lg me-1"></i> প্রথম চ্যাপ্টার তৈরি করুন
                            </button>
                        </div>
                    </div>

                    <div v-else class="chapters-container d-flex flex-column gap-3">
                        <div
                            v-for="(chapter, cIndex) in currentCourse.chapters"
                            :key="chapter.id"
                            class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white"
                        >
                            <!-- Chapter Header -->
                            <div class="card-header bg-light border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary me-2">চ্যাপ্টার {{ cIndex + 1 }}</span>
                                    <strong class="text-dark">{{ chapter.title }}</strong>
                                    <p v-if="chapter.description" class="smaller text-muted mb-0 mt-1">{{ chapter.description }}</p>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button class="btn btn-primary btn-sm py-1 px-2" @click="openAddLesson(chapter.id)">
                                        <i class="bi bi-plus-lg me-1"></i> লেসন যোগ করুন
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm py-1 px-2" title="চ্যাপ্টার মুছুন" @click="handleDeleteChapter(chapter)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Lessons List inside Chapter -->
                            <div class="card-body p-0">
                                <div v-if="!chapter.lessons || chapter.lessons.length === 0" class="text-center py-4 text-muted smaller">
                                    এই চ্যাপ্টারে এখনো কোনো লেসন যুক্ত করা হয়নি। ডানপাশের লাইব্রেরি থেকে যুক্ত করুন।
                                </div>

                                <ul v-else class="list-group list-group-flush">
                                    <li
                                        v-for="(lesson, lIndex) in chapter.lessons"
                                        :key="lesson.id"
                                        class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 hover-bg transition"
                                    >
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge rounded-circle bg-light text-secondary border p-2 smaller">
                                                {{ lIndex + 1 }}
                                            </span>
                                            <div>
                                                <div class="fw-semibold text-dark small">
                                                    {{ lesson.title }}
                                                </div>
                                                <div class="smaller text-muted d-flex align-items-center gap-2 mt-1">
                                                    <span v-if="lesson.is_linked_to_article" class="text-primary">
                                                        <i class="bi bi-youtube me-1"></i> লিঙ্কড ভিডিও আর্টিকেল
                                                    </span>
                                                    <span v-else class="text-secondary">
                                                        <i class="bi bi-journal-text me-1"></i> কাস্টম নোটস
                                                    </span>
                                                    <span v-if="lesson.is_completed" class="badge bg-success-subtle text-success py-0 px-1">
                                                        <i class="bi bi-check2"></i> সম্পন্ন
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <router-link :to="`/lessons/${lesson.id}`" class="btn btn-outline-primary btn-sm py-1 px-2">
                                                <i class="bi bi-book me-1"></i> পড়ুন
                                            </router-link>
                                            <button class="btn btn-outline-danger btn-sm py-1 px-2" @click="handleDeleteLesson(lesson)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Unassigned Articles Browser -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 sticky-top" style="top: 80px;">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="bi bi-inbox text-primary me-1"></i>অ্যাসাইন ছাড়া আর্টিকেল
                            </h6>
                            <span class="badge bg-primary-subtle text-primary">{{ unassignedArticles.length }}</span>
                        </div>

                        <p class="smaller text-secondary mb-3">
                            যেসব ভিডিও আর্টিকেল এখনো কোনো কোর্সে অন্তর্ভুক্ত করা হয়নি, সেগুলোকে এক ক্লিকে চ্যাপ্টারে লেসন হিসেবে যুক্ত করুন:
                        </p>

                        <div v-if="unassignedArticles.length === 0" class="text-center py-4 text-muted smaller">
                            <i class="bi bi-check2-all text-success fs-3 d-block mb-1"></i>
                            সবগুলো আর্টিকেল কোনো না কোনো কোর্সে অন্তর্ভুক্ত রয়েছে!
                        </div>

                        <div v-else class="d-flex flex-column gap-2" style="max-height: 480px; overflow-y: auto;">
                            <div
                                v-for="item in unassignedArticles"
                                :key="item.id"
                                class="p-2 border rounded-3 bg-light d-flex flex-column gap-1"
                            >
                                <div class="fw-semibold smaller text-dark text-truncate" :title="item.bengali_title || item.title">
                                    {{ item.bengali_title || item.title }}
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <span class="smaller text-muted">{{ item.reading_time_minutes }} মিনিট</span>

                                    <!-- Quick Assign Dropdown if chapters exist -->
                                    <div v-if="currentCourse.chapters && currentCourse.chapters.length > 0" class="dropdown">
                                        <button class="btn btn-primary btn-sm py-0 px-2 smaller dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            + চ্যাপ্টারে যোগ করুন
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                            <li v-for="ch in currentCourse.chapters" :key="ch.id">
                                                <button class="dropdown-item" @click="openAddLesson(ch.id, item)">
                                                    {{ ch.title }}
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Chapter Modal -->
        <div v-if="showChapterModal" class="modal-backdrop fade show"></div>
        <div v-if="showChapterModal" class="modal fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0">নতুন চ্যাপ্টার তৈরি করুন</h5>
                        <button type="button" class="btn-close" @click="showChapterModal = false"></button>
                    </div>
                    <form @submit.prevent="handleAddChapter">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">চ্যাপ্টারের শিরোনাম *</label>
                                <input v-model="chapterTitle" type="text" class="form-control" placeholder="উদাঃ পার্ট ১: ডেটাবেজ অপ্টিমাইজেশন" required autofocus />
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">সংক্ষিপ্ত বিবরণ (ঐচ্ছিক)</label>
                                <textarea v-model="chapterDesc" rows="2" class="form-control" placeholder="চ্যাপ্টারের মূল বিষয়..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-top py-3 bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="showChapterModal = false">বাতিল</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4" :disabled="isSaving || !chapterTitle.trim()">
                                চ্যাপ্টার তৈরি করুন
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Lesson Modal -->
        <div v-if="showLessonModal" class="modal-backdrop fade show"></div>
        <div v-if="showLessonModal" class="modal fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0">চ্যাপ্টারে লেসন যুক্ত করুন</h5>
                        <button type="button" class="btn-close" @click="showLessonModal = false"></button>
                    </div>
                    <form @submit.prevent="handleAddLesson">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">লেসনের শিরোনাম *</label>
                                <input v-model="lessonTitle" type="text" class="form-control" placeholder="উদাঃ মাইগ্রেশন ও ইনডেক্সিং গাইড" required autofocus />
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">সংরক্ষিত ভিডিও আর্টিকেল লিঙ্ক করুন (ঐচ্ছিক)</label>
                                <select v-model="selectedArticleId" class="form-select form-select-sm">
                                    <option value="">-- কোনো আর্টিকেল লিঙ্ক করতে না চাইলে ফাঁকা রাখুন --</option>
                                    <option v-for="art in unassignedArticles" :key="art.id" :value="art.id">
                                        {{ art.bengali_title || art.title }} ({{ art.channel_name }})
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">কাস্টম লেসন নোটস / বক্তব্য (ঐচ্ছিক)</label>
                                <textarea
                                    v-model="lessonContent"
                                    rows="5"
                                    class="form-control"
                                    placeholder="যদি ভিডিও লিংক না থাকে বা অতিরিক্ত নোট যোগ করতে চান তবে এখানে লিখুন..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-top py-3 bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="showLessonModal = false">বাতিল</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4" :disabled="isSaving || !lessonTitle.trim()">
                                লেসন সংরক্ষণ করুন
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.8rem;
}
.hover-bg:hover {
    background-color: var(--surface-sunken);
}
.transition {
    transition: background-color 0.15s ease-in-out;
}
</style>

