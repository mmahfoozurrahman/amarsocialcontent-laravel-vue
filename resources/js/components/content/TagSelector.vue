<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { useNotification } from '@/composables/useNotification';

const props = defineProps({
    contentId: {
        type: Number,
        required: true,
    },
    initialTags: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['updated']);

const { success, error } = useNotification();
const tags = ref([...props.initialTags]);
const userTags = ref([]);
const newTagInput = ref('');
const isSaving = ref(false);

const fetchUserTags = async () => {
    try {
        const res = await api.get('/user/tags');
        userTags.value = res.data.data;
    } catch (e) {
        // Ignore
    }
};

const addTag = async (tagName) => {
    const cleaned = tagName.trim();
    if (!cleaned) return;

    if (tags.value.some((t) => t.name.toLowerCase() === cleaned.toLowerCase())) {
        newTagInput.value = '';
        return;
    }

    const updatedTagNames = [...tags.value.map((t) => t.name), cleaned];
    await syncTags(updatedTagNames);
    newTagInput.value = '';
};

const removeTag = async (tagToRemove) => {
    const updatedTagNames = tags.value
        .filter((t) => t.id !== tagToRemove.id)
        .map((t) => t.name);

    await syncTags(updatedTagNames);
};

const syncTags = async (tagNames) => {
    isSaving.value = true;
    try {
        const res = await api.post(`/user/articles/${props.contentId}/tags`, {
            tags: tagNames,
        });
        tags.value = res.data.data;
        emit('updated', tags.value);
        success('ট্যাগ আপডেট করা হয়েছে।');
    } catch (err) {
        error('ট্যাগ সেভ করতে সমস্যা হয়েছে।');
    } finally {
        isSaving.value = false;
    }
};

onMounted(() => {
    fetchUserTags();
});
</script>

<template>
    <div class="tag-selector mb-3">
        <label class="form-label small fw-semibold text-secondary d-flex align-items-center justify-content-between mb-2">
            <span><i class="bi bi-tags me-1"></i> ট্যাগসমূহ</span>
            <span v-if="isSaving" class="spinner-border spinner-border-sm text-primary"></span>
        </label>

        <!-- Current Tags Pills -->
        <div class="d-flex flex-wrap gap-1 mb-2">
            <span
                v-for="tag in tags"
                :key="tag.id || tag.name"
                class="badge bg-light text-primary border border-primary-subtle d-inline-flex align-items-center gap-1 py-1 px-2 rounded-pill"
            >
                #{{ tag.name }}
                <button
                    type="button"
                    class="btn-close ms-1"
                    style="font-size: 0.55rem;"
                    aria-label="Remove"
                    @click="removeTag(tag)"
                ></button>
            </span>
            <span v-if="tags.length === 0" class="text-muted smaller">কোনো ট্যাগ যুক্ত করা হয়নি।</span>
        </div>

        <!-- Add Tag Input -->
        <div class="input-group input-group-sm">
            <input
                v-model="newTagInput"
                type="text"
                class="form-control"
                placeholder="নতুন ট্যাগ লিখুন এবং Enter চাপুন..."
                @keydown.enter.prevent="addTag(newTagInput)"
            />
            <button
                type="button"
                class="btn btn-outline-primary"
                :disabled="!newTagInput.trim() || isSaving"
                @click="addTag(newTagInput)"
            >
                যুক্ত করুন
            </button>
        </div>

        <!-- Quick Select Suggestions -->
        <div v-if="userTags.length > 0" class="mt-2">
            <span class="smaller text-muted me-1">পরামর্শ:</span>
            <button
                v-for="t in userTags.slice(0, 5)"
                :key="t.id"
                type="button"
                class="btn btn-link btn-sm p-0 me-2 text-decoration-none smaller"
                @click="addTag(t.name)"
            >
                +{{ t.name }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.75rem;
}
</style>

