<script setup>
import { ref } from 'vue';
import { useContent } from '@/composables/useContent';
import { useNotification } from '@/composables/useNotification';

const emit = defineEmits(['updated']);

const { submitManualTranscript, isSubmitting } = useContent();
const { success, error } = useNotification();

const show = ref(false);
const contentId = ref(null);
const videoTitle = ref('');
const transcript = ref('');
const errors = ref({});

const openModal = (content) => {
    contentId.value = content.id;
    videoTitle.value = content.title || 'ইউটিউব ভিডিও';
    transcript.value = '';
    errors.value = {};
    show.value = true;
};

const closeModal = () => {
    if (isSubmitting.value) return;
    show.value = false;
};

const handleSubmit = async () => {
    errors.value = {};
    const text = transcript.value.trim();
    if (!text || text.length < 20) {
        errors.value.transcript = 'অনুগ্রহ করে অন্তত ২০ অক্ষরের ট্রান্সক্রিপ্ট প্রদান করুন।';
        return;
    }

    try {
        const res = await submitManualTranscript(contentId.value, text);
        if (res.success) {
            success(res.message || 'ম্যানুয়াল ট্রান্সক্রিপ্ট সফলভাবে গ্রহণ করা হয়েছে।');
            emit('updated', res.data);
            closeModal();
        } else {
            error(res.message || 'প্রসেসিং শুরু করতে সমস্যা হয়েছে।');
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.message || 'ম্যানুয়াল ট্রান্সক্রিপ্ট প্রসেস করতে ব্যর্থ হয়েছে।';
        error(msg);
    }
};

defineExpose({ openModal });
</script>

<template>
    <div>
        <div v-if="show" class="modal-backdrop fade show"></div>
        <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0">
                            <i class="bi bi-file-earmark-text text-primary me-2"></i>ম্যানুয়াল ট্রান্সক্রিপ্ট ইনপুট
                        </h5>
                        <button type="button" class="btn-close" :disabled="isSubmitting" @click="closeModal" aria-label="Close"></button>
                    </div>

                    <form @submit.prevent="handleSubmit">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">ভিডিও শিরোনাম</label>
                                <div class="p-2 bg-light border rounded text-dark small fw-medium">
                                    {{ videoTitle }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="manualText" class="form-label small fw-semibold text-secondary">
                                    ট্রান্সক্রিপ্ট বা মূল বক্তব্য <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    id="manualText"
                                    v-model="transcript"
                                    rows="8"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.transcript }"
                                    placeholder="ভিডিওর বক্তব্য বা অডিও ট্রান্সক্রিপ্ট এখানে পেস্ট করুন..."
                                    required
                                    autofocus
                                ></textarea>
                                <div v-if="errors.transcript" class="invalid-feedback-custom">
                                    <i class="bi bi-exclamation-circle"></i> {{ errors.transcript }}
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
                                আর্টিকেল তৈরি করুন
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

