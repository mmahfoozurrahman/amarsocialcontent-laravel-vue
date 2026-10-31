<script setup>
import { ref } from 'vue';
import api from '@/services/api';
import { useNotification } from '@/composables/useNotification';

const props = defineProps({
    contentId: {
        type: Number,
        required: true,
    },
    initialNote: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['updated']);

const { success, error } = useNotification();
const noteContent = ref(props.initialNote || '');
const isSaving = ref(false);
const isEditing = ref(!props.initialNote);

const handleSave = async () => {
    if (!noteContent.value.trim()) return;
    isSaving.value = true;
    try {
        const res = await api.post(`/user/articles/${props.contentId}/notes`, {
            note_content: noteContent.value.trim(),
        });
        success('ব্যক্তিগত নোট সংরক্ষিত হয়েছে।');
        isEditing.value = false;
        emit('updated', noteContent.value);
    } catch (err) {
        error('নোট সেভ করতে সমস্যা হয়েছে।');
    } finally {
        isSaving.value = false;
    }
};

const handleDelete = async () => {
    if (!confirm('আপনি কি এই নোটটি মুছে ফেলতে চান?')) return;
    isSaving.value = true;
    try {
        await api.delete(`/user/articles/${props.contentId}/notes`);
        noteContent.value = '';
        isEditing.value = true;
        success('নোট মুছে ফেলা হয়েছে।');
        emit('updated', '');
    } catch (err) {
        error('নোট মুছে ফেলতে সমস্যা হয়েছে।');
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-1 text-dark">
                <i class="bi bi-journal-bookmark-fill text-warning"></i>
                <span class="fw-semibold small">ব্যক্তিগত নোটস (Private)</span>
            </div>
            <div class="d-flex gap-1">
                <button
                    v-if="!isEditing && noteContent"
                    class="btn btn-outline-secondary btn-sm py-0 px-2"
                    title="সম্পাদনা করুন"
                    @click="isEditing = true"
                >
                    <i class="bi bi-pencil small"></i>
                </button>
                <button
                    v-if="noteContent"
                    class="btn btn-outline-danger btn-sm py-0 px-2"
                    title="নোট মুছুন"
                    @click="handleDelete"
                >
                    <i class="bi bi-trash small"></i>
                </button>
            </div>
        </div>

        <div class="card-body p-3">
            <div v-if="!isEditing && noteContent" class="note-display text-secondary small bg-light p-3 rounded border">
                <p class="mb-0 text-pre-wrap">{{ noteContent }}</p>
            </div>

            <div v-else>
                <textarea
                    v-model="noteContent"
                    rows="4"
                    class="form-control form-control-sm mb-2"
                    placeholder="এই ভিডিও থেকে আপনার ব্যক্তিগত ভাবনা, প্রশ্ন বা গুরুত্বপূর্ণ নোট লিখে রাখুন..."
                ></textarea>
                <div class="d-flex justify-content-end gap-2">
                    <button
                        v-if="props.initialNote"
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        :disabled="isSaving"
                        @click="isEditing = false"
                    >
                        বাতিল
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm px-3"
                        :disabled="isSaving || !noteContent.trim()"
                        @click="handleSave"
                    >
                        <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-check-lg me-1"></i>
                        নোট সেভ করুন
                    </button>
                </div>
            </div>
            <div class="smaller text-muted mt-2">
                <i class="bi bi-lock me-1"></i> এই নোটটি সম্পূর্ণ ব্যক্তিগত এবং পাবলিক শেয়ারিংয়ে দৃশ্যমান হবে না।
            </div>
        </div>
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.75rem;
}
.text-pre-wrap {
    white-space: pre-wrap;
}
</style>

