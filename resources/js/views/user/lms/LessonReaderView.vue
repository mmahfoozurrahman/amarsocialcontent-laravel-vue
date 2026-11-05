<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useLms } from '@/composables/useLms';
import { useNotification } from '@/composables/useNotification';
import { renderMarkdown } from '@/services/markdown';
import EmbeddedYouTubePlayer from '@/components/content/EmbeddedYouTubePlayer.vue';

const route = useRoute();
const router = useRouter();
const lessonId = computed(() => Number(route.params.id));

const { currentLesson, isLoading, fetchLesson, toggleLessonComplete } = useLms();
const { success, error } = useNotification();

const course = computed(() => currentLesson.value?.chapter?.course);
const chapters = computed(() => course.value?.chapters || []);

// Flatten all lessons across chapters for next/prev navigation
const allLessons = computed(() => {
    const list = [];
    chapters.value.forEach((ch) => {
        (ch.lessons || []).forEach((les) => {
            list.push({ ...les, chapterTitle: ch.title });
        });
    });
    return list;
});

const currentIndex = computed(() => {
    return allLessons.value.findIndex((l) => l.id === lessonId.value);
});

const prevLesson = computed(() => {
    if (currentIndex.value > 0) return allLessons.value[currentIndex.value - 1];
    return null;
});

const nextLesson = computed(() => {
    if (currentIndex.value >= 0 && currentIndex.value < allLessons.value.length - 1) {
        return allLessons.value[currentIndex.value + 1];
    }
    return null;
});

// Render Markdown Content
const renderedHtml = computed(() => renderMarkdown(currentLesson.value?.aggregated_content?.article?.raw_markdown || currentLesson.value?.content));

// Toggle completion
const handleToggleComplete = async () => {
    const nextState = !currentLesson.value.is_completed;
    try {
        await toggleLessonComplete(lessonId.value, nextState);
        success(nextState ? 'লেসনটি সম্পন্ন হিসেবে চিহ্নিত হয়েছে!' : 'অসম্পন্ন হিসেবে চিহ্নিত হয়েছে।');
    } catch (e) {
        error('স্ট্যাটাস আপডেট করা যায়নি।');
    }
};

const goToLesson = (id) => {
    router.push(`/lessons/${id}`);
};

watch(lessonId, () => {
    fetchLesson(lessonId.value);
});

onMounted(() => {
    fetchLesson(lessonId.value);
});
</script>

<template>
    <div class="lesson-reader-page py-4">
        <div class="container-fluid px-lg-5">
            <!-- Loading -->
            <div v-if="isLoading && !currentLesson" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">লেসন লোড হচ্ছে...</p>
            </div>

            <div v-else-if="currentLesson" class="row g-4">
                <!-- Left Sidebar: Course Tree Navigation -->
                <div class="col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 sticky-top" style="top: 80px;">
                        <router-link :to="`/courses/${course?.id}/builder`" class="btn btn-outline-secondary btn-sm mb-3 w-100">
                            <i class="bi bi-arrow-left me-1"></i> কোর্সে ফিরে যান
                        </router-link>

                        <h6 class="fw-bold text-dark mb-1 text-truncate" :title="course?.title">
                            {{ course?.title }}
                        </h6>
                        <div class="smaller text-muted mb-3">কোর্স সিলেবাস ও প্রগ্রেস</div>

                        <!-- Chapters & Lessons Tree -->
                        <div class="course-tree d-flex flex-column gap-3" style="max-height: calc(100vh - 240px); overflow-y: auto;">
                            <div v-for="(ch, cIdx) in chapters" :key="ch.id" class="border rounded-3 p-2 bg-light">
                                <div class="fw-semibold smaller text-secondary mb-2 d-flex align-items-center gap-1">
                                    <i class="bi bi-folder2 text-primary"></i>
                                    <span class="text-truncate">{{ cIdx + 1 }}. {{ ch.title }}</span>
                                </div>

                                <div class="d-flex flex-column gap-1">
                                    <button
                                        v-for="les in ch.lessons"
                                        :key="les.id"
                                        type="button"
                                        class="btn btn-sm text-start py-1 px-2 rounded d-flex align-items-center justify-content-between smaller"
                                        :class="les.id === lessonId ? 'btn-primary text-white' : 'btn-light text-dark'"
                                        @click="goToLesson(les.id)"
                                    >
                                        <span class="text-truncate" style="max-width: 180px;">{{ les.title }}</span>
                                        <i
                                            class="bi"
                                            :class="les.is_completed ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted'"
                                        ></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Lesson Player & Material Content -->
                <div class="col-lg-9">
                    <!-- Top Action / Nav Bar -->
                    <div class="card border-0 shadow-sm p-3 bg-white rounded-4 mb-4 d-flex flex-row justify-content-between align-items-center">
                        <div>
                            <span class="smaller text-muted d-block">{{ currentLesson.chapter?.title }}</span>
                            <h4 class="fw-bold text-dark mb-0">{{ currentLesson.title }}</h4>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <!-- Prev Lesson -->
                            <button
                                class="btn btn-outline-secondary btn-sm"
                                :disabled="!prevLesson"
                                @click="prevLesson && goToLesson(prevLesson.id)"
                            >
                                <i class="bi bi-chevron-left me-1"></i> পূর্ববর্তী
                            </button>

                            <!-- Complete Toggle Button -->
                            <button
                                class="btn btn-sm px-3"
                                :class="currentLesson.is_completed ? 'btn-success' : 'btn-outline-success'"
                                @click="handleToggleComplete"
                            >
                                <i class="bi" :class="currentLesson.is_completed ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                                <span class="ms-1">{{ currentLesson.is_completed ? 'পড়া সম্পন্ন' : 'Mark as Read' }}</span>
                            </button>

                            <!-- Next Lesson -->
                            <button
                                class="btn btn-outline-primary btn-sm"
                                :disabled="!nextLesson"
                                @click="nextLesson && goToLesson(nextLesson.id)"
                            >
                                পরবর্তী <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- In-App Embedded YouTube Player if article linked -->
                    <EmbeddedYouTubePlayer
                        v-if="currentLesson.aggregated_content"
                        :video-id="currentLesson.aggregated_content.youtube_video_id"
                        :content-type="currentLesson.aggregated_content.content_type"
                        :title="currentLesson.aggregated_content.title"
                    />

                    <!-- Lesson Content Card -->
                    <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4 mb-4">
                        <div class="markdown-body prose" v-html="renderedHtml"></div>
                    </div>

                    <!-- Bottom Navigation Buttons -->
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <button
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="!prevLesson"
                            @click="prevLesson && goToLesson(prevLesson.id)"
                        >
                            <i class="bi bi-chevron-left me-1"></i> পূর্ববর্তী লেসন
                        </button>

                        <button
                            v-if="nextLesson"
                            class="btn btn-primary btn-sm px-4"
                            @click="goToLesson(nextLesson.id)"
                        >
                            পরবর্তী লেসন: {{ nextLesson.title }} <i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.8rem;
}
</style>

