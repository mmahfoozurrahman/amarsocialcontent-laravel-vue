<script setup>
import { ref, computed } from 'vue';
import { useContent } from '@/composables/useContent';
import { useNotification } from '@/composables/useNotification';

const emit = defineEmits(['saved']);

const { submitYouTubeUrl, isSubmitting } = useContent();
const { success, error } = useNotification();

const show = ref(false);
const youtubeUrl = ref('');
const showManualTranscript = ref(false);
const manualTranscript = ref('');
const errors = ref({});

// Live format detection
const detectedType = computed(() => {
    const url = youtubeUrl.value.trim();
    if (!url) return null;
    if (url.includes('/shorts/')) return 'short';
    if (url.includes('youtube.com/watch') || url.includes('youtu.be/')) return 'video';
    return 'unknown';
});

const openModal = () => {
    youtubeUrl.value = '';
    manualTranscript.value = '';
    showManualTranscript.value = false;
    errors.value = {};
    show.value = true;
};

const closeModal = () => {
    if (isSubmitting.value) return;
    show.value = false;
};

const handleSubmit = async () => {
    errors.value = {};
    if (!youtubeUrl.value.trim()) {
        errors.value.youtube_url = 'অনুগ্রহ করে একটি ইউটিউব ভিডিও বা শর্টসের লিঙ্ক দিন।';
        return;
    }

    try {
        const res = await submitYouTubeUrl(
            youtubeUrl.value.trim(),
            showManualTranscript.value ? manualTranscript.value.trim() : null
        );

        success(res.message || 'ভিডিও সফলভাবে যুক্ত হয়েছে এবং প্রসেস শুরু হয়েছে।');
        emit('saved', res.data);
        closeModal();
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.errors) {
            errors.value = Object.fromEntries(
                Object.entries(err.response.data.errors).map(([k, v]) => [k, v[0]])
            );
        } else {
            error(err.response?.data?.message || 'ভিডিও প্রসেসিং শুরু করতে সমস্যা হয়েছে।');
        }
    }
};

defineExpose({ openModal });
</script>

<template>
    <div>
        <!-- Modal Backdrop & Dialog -->
        <div v-if="show" class="modal-backdrop fade show"></div>
        <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="bg-danger text-white rounded p-1 d-inline-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="bi bi-youtube"></i>
                            </span>
                            <h5 class="modal-title fw-bold text-dark mb-0">নতুন ইউটিউব ভিডিও রূপান্তর করুন</h5>
                        </div>
                        <button type="button" class="btn-close" :disabled="isSubmitting" @click="closeModal" aria-label="Close"></button>
                    </div>

                    <form @submit.prevent="handleSubmit" novalidate>
                        <div class="modal-body p-4">
                            <!-- AI Key Notice & Link to Profile -->
                            <div class="alert alert-light border small d-flex flex-wrap align-items-center justify-content-between gap-1 p-2 px-3 mb-3 rounded-3">
                                <div class="d-flex align-items-center gap-2 text-secondary">
                                    <i class="bi bi-shield-lock-fill text-primary"></i>
                                    <span>আপনার নিজস্ব Groq বা Gemini API Key দিয়ে কনটেন্ট প্রস্তুত হবে।</span>
                                </div>
                                <router-link to="/profile?tab=ai" class="btn btn-link btn-sm p-0 smaller text-primary text-decoration-none fw-semibold" @click="closeModal">
                                    কী সেট বা পরিবর্তন করুন <i class="bi bi-arrow-right"></i>
                                </router-link>
                            </div>

                            <!-- URL Field -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="youtubeUrl" class="form-label small fw-semibold text-secondary mb-0">
                                        YouTube Video বা Shorts URL <span class="text-danger">*</span>
                                    </label>
                                    <span v-if="detectedType === 'short'" class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-lightning-fill me-1"></i> YouTube Shorts
                                    </span>
                                    <span v-else-if="detectedType === 'video'" class="badge bg-primary-subtle text-primary">
                                        <i class="bi bi-play-btn-fill me-1"></i> Regular Video
                                    </span>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-link-45deg text-muted"></i>
                                    </span>
                                    <input
                                        id="youtubeUrl"
                                        v-model="youtubeUrl"
                                        type="url"
                                        class="form-control border-start-0"
                                        :class="{ 'is-invalid': errors.youtube_url }"
                                        placeholder="https://www.youtube.com/watch?v=... বা https://youtube.com/shorts/..."
                                        required
                                        autofocus
                                    />
                                </div>
                                <div v-if="errors.youtube_url" class="invalid-feedback-custom">
                                    <i class="bi bi-exclamation-circle"></i> {{ errors.youtube_url }}
                                </div>
                                <div class="form-text smaller text-muted mt-1">
                                    আমরা স্বয়ংক্রিয়ভাবে ভিডিওর তথ্য ও ক্যাপশন সংগ্রহ করে বাংলায় বিশদ মেন্টর-স্টাইল আর্টিকেল প্রস্তুত করব।
                                </div>
                            </div>

                            <!-- Optional Manual Transcript Toggle -->
                            <div class="mb-3 pt-2 border-top">
                                <div class="form-check form-switch mb-2">
                                    <input
                                        id="toggleTranscript"
                                        v-model="showManualTranscript"
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                    />
                                    <label class="form-check-label small fw-semibold text-secondary" for="toggleTranscript">
                                        ম্যানুয়ালি ট্রান্সক্রিপ্ট যুক্ত করতে চান? (ঐচ্ছিক)
                                    </label>
                                </div>
                                <div v-if="showManualTranscript" class="mt-2">
                                    <textarea
                                        v-model="manualTranscript"
                                        rows="4"
                                        class="form-control"
                                        placeholder="ভিডিওতে অটো-ক্যাপশন না থাকলে এখানে স্পিকারের মূল কথাগুলো বা ট্রান্সক্রিপ্ট পেস্ট করতে পারেন..."
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Mentor Style Assurance Note -->
                            <div class="p-3 bg-light rounded-3 d-flex gap-2 align-items-start border">
                                <i class="bi bi-info-circle text-primary mt-1"></i>
                                <div class="smaller text-secondary">
                                    <strong>জ্ঞান সংরক্ষণের নিশ্চয়তা:</strong> আমাদের এআই মেন্টর ভিডিওর গভীরতা বজায় রেখে সমস্ত গুরুত্বপূর্ণ যুক্তি, বাস্তব উদাহরণ ও ইংরেজি টার্মস সংরক্ষণ করে বাংলায় পূর্ণাঙ্গ ম্যাটেরিয়াল তৈরি করে।
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top py-3 bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" :disabled="isSubmitting" @click="closeModal">
                                বাতিল
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold" :disabled="isSubmitting">
                                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <i v-else class="bi bi-stars me-1"></i>
                                রূপান্তর শুরু করুন
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
</style>

