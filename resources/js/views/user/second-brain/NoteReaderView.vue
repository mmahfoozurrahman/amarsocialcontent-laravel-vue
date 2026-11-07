<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSecondBrain } from '@/composables/useSecondBrain';
import { useNotification } from '@/composables/useNotification';
import { renderMarkdown } from '@/services/markdown';
import EmbeddedYouTubePlayer from '@/components/content/EmbeddedYouTubePlayer.vue';

const route = useRoute();
const router = useRouter();
const noteId = computed(() => Number(route.params.id));

const { currentNote, isLoading, fetchNote, toggleNoteComplete } = useSecondBrain();
const { success, error } = useNotification();

const collection = computed(() => currentNote.value?.section?.collection);
const sections = computed(() => collection.value?.sections || []);

// Flatten all notes across sections for next/prev navigation
const allNotes = computed(() => {
    const list = [];
    sections.value.forEach((ch) => {
        (ch.notes || []).forEach((les) => {
            list.push({ ...les, sectionTitle: ch.title });
        });
    });
    return list;
});

const currentIndex = computed(() => {
    return allNotes.value.findIndex((l) => l.id === noteId.value);
});

const prevNote = computed(() => {
    if (currentIndex.value > 0) return allNotes.value[currentIndex.value - 1];
    return null;
});

const nextNote = computed(() => {
    if (currentIndex.value >= 0 && currentIndex.value < allNotes.value.length - 1) {
        return allNotes.value[currentIndex.value + 1];
    }
    return null;
});

// Render Markdown Content
const renderedHtml = computed(() => renderMarkdown(currentNote.value?.aggregated_content?.article?.raw_markdown || currentNote.value?.content));

// Toggle completion
const handleToggleComplete = async () => {
    const nextState = !currentNote.value.is_completed;
    try {
        await toggleNoteComplete(noteId.value, nextState);
        success(nextState ? 'নোটসটি সম্পন্ন হিসেবে চিহ্নিত হয়েছে!' : 'অসম্পন্ন হিসেবে চিহ্নিত হয়েছে।');
    } catch (e) {
        error('স্ট্যাটাস আপডেট করা যায়নি।');
    }
};

const goToNote = (id) => {
    router.push(`/notes/${id}`);
};

watch(noteId, () => {
    fetchNote(noteId.value);
});

onMounted(() => {
    fetchNote(noteId.value);
});
</script>

<template>
    <div class="note-reader-page py-4">
        <div class="container-fluid px-lg-5">
            <!-- Loading -->
            <div v-if="isLoading && !currentNote" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">নোটস লোড হচ্ছে...</p>
            </div>

            <div v-else-if="currentNote" class="row g-4">
                <!-- Left Sidebar: Collection Tree Navigation -->
                <div class="col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 sticky-top" style="top: 80px;">
                        <router-link :to="`/collections/${collection?.id}/builder`" class="btn btn-outline-secondary btn-sm mb-3 w-100">
                            <i class="bi bi-arrow-left me-1"></i> কালেকশনসে ফিরে যান
                        </router-link>

                        <h6 class="fw-bold text-dark mb-1 text-truncate" :title="collection?.title">
                            {{ collection?.title }}
                        </h6>
                        <div class="smaller text-muted mb-3">কালেকশনস সিলেবাস ও প্রগ্রেস</div>

                        <!-- Sections & Notes Tree -->
                        <div class="collection-tree d-flex flex-column gap-3" style="max-height: calc(100vh - 240px); overflow-y: auto;">
                            <div v-for="(ch, cIdx) in sections" :key="ch.id" class="border rounded-3 p-2 bg-light">
                                <div class="fw-semibold smaller text-secondary mb-2 d-flex align-items-center gap-1">
                                    <i class="bi bi-folder2 text-primary"></i>
                                    <span class="text-truncate">{{ cIdx + 1 }}. {{ ch.title }}</span>
                                </div>

                                <div class="d-flex flex-column gap-1">
                                    <button
                                        v-for="les in ch.notes"
                                        :key="les.id"
                                        type="button"
                                        class="btn btn-sm text-start py-1 px-2 rounded d-flex align-items-center justify-content-between smaller"
                                        :class="les.id === noteId ? 'btn-primary text-white' : 'btn-light text-dark'"
                                        @click="goToNote(les.id)"
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

                <!-- Right: Note Player & Material Content -->
                <div class="col-lg-9">
                    <!-- Top Action / Nav Bar -->
                    <div class="card border-0 shadow-sm p-3 bg-white rounded-4 mb-4 d-flex flex-row justify-content-between align-items-center">
                        <div>
                            <span class="smaller text-muted d-block">{{ currentNote.section?.title }}</span>
                            <h4 class="fw-bold text-dark mb-0">{{ currentNote.title }}</h4>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <!-- Prev Note -->
                            <button
                                class="btn btn-outline-secondary btn-sm"
                                :disabled="!prevNote"
                                @click="prevNote && goToNote(prevNote.id)"
                            >
                                <i class="bi bi-chevron-left me-1"></i> পূর্ববর্তী
                            </button>

                            <!-- Complete Toggle Button -->
                            <button
                                class="btn btn-sm px-3"
                                :class="currentNote.is_completed ? 'btn-success' : 'btn-outline-success'"
                                @click="handleToggleComplete"
                            >
                                <i class="bi" :class="currentNote.is_completed ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                                <span class="ms-1">{{ currentNote.is_completed ? 'পড়া সম্পন্ন' : 'Mark as Read' }}</span>
                            </button>

                            <!-- Next Note -->
                            <button
                                class="btn btn-outline-primary btn-sm"
                                :disabled="!nextNote"
                                @click="nextNote && goToNote(nextNote.id)"
                            >
                                পরবর্তী <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- In-App Embedded YouTube Player if article linked -->
                    <EmbeddedYouTubePlayer
                        v-if="currentNote.aggregated_content"
                        :video-id="currentNote.aggregated_content.youtube_video_id"
                        :content-type="currentNote.aggregated_content.content_type"
                        :title="currentNote.aggregated_content.title"
                    />

                    <!-- Note Content Card -->
                    <div class="card border-0 shadow-sm p-4 p-md-5 bg-white rounded-4 mb-4">
                        <div class="markdown-body prose" v-html="renderedHtml"></div>
                    </div>

                    <!-- Bottom Navigation Buttons -->
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <button
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="!prevNote"
                            @click="prevNote && goToNote(prevNote.id)"
                        >
                            <i class="bi bi-chevron-left me-1"></i> পূর্ববর্তী নোটস
                        </button>

                        <button
                            v-if="nextNote"
                            class="btn btn-primary btn-sm px-4"
                            @click="goToNote(nextNote.id)"
                        >
                            পরবর্তী নোটস: {{ nextNote.title }} <i class="bi bi-chevron-right ms-1"></i>
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

