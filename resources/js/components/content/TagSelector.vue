<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
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

// Searchable tag-suggestion dropdown state
const isDropdownOpen = ref(false);
const highlightedIndex = ref(-1);
const selectorRef = ref(null);
const MAX_SUGGESTIONS = 8;

const appliedNames = computed(() => tags.value.map((t) => t.name.toLowerCase()));

const trimmedInput = computed(() => newTagInput.value.trim());

const filteredSuggestions = computed(() => {
    const query = trimmedInput.value.toLowerCase();

    return userTags.value
        .filter((t) => !appliedNames.value.includes(t.name.toLowerCase()))
        .filter((t) => query === '' || t.name.toLowerCase().includes(query))
        .slice(0, MAX_SUGGESTIONS);
});

const canCreateNew = computed(() => {
    const query = trimmedInput.value.toLowerCase();
    if (query === '') return false;

    const known = new Set([
        ...userTags.value.map((t) => t.name.toLowerCase()),
        ...appliedNames.value,
    ]);

    return !known.has(query);
});

// Existing matches first, then an optional "create new tag" entry.
const dropdownOptions = computed(() => {
    const options = filteredSuggestions.value.map((t) => ({ name: t.name, isNew: false }));

    if (canCreateNew.value) {
        options.push({ name: trimmedInput.value, isNew: true });
    }

    return options;
});

const openDropdown = () => {
    if (dropdownOptions.value.length > 0) {
        isDropdownOpen.value = true;
    }
};

const closeDropdown = () => {
    isDropdownOpen.value = false;
    highlightedIndex.value = -1;
};

const moveHighlight = (direction) => {
    const count = dropdownOptions.value.length;
    if (count === 0) return;

    if (!isDropdownOpen.value) {
        isDropdownOpen.value = true;
    }

    if (highlightedIndex.value === -1) {
        highlightedIndex.value = direction > 0 ? 0 : count - 1;
        return;
    }

    highlightedIndex.value = (highlightedIndex.value + direction + count) % count;
};

const handleInput = () => {
    highlightedIndex.value = -1;
    openDropdown();
};

const selectOption = (option) => {
    if (!option) return;
    addTag(option.name);
    closeDropdown();
};

const onEnter = () => {
    const option = isDropdownOpen.value ? dropdownOptions.value[highlightedIndex.value] : null;

    if (option) {
        selectOption(option);
        return;
    }

    // Default behaviour: treat the typed text as a brand-new tag.
    addTag(newTagInput.value);
    closeDropdown();
};

const handleOutsideClick = (event) => {
    if (selectorRef.value && !selectorRef.value.contains(event.target)) {
        closeDropdown();
    }
};

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
    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
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

        <!-- Add / Search Tag Input with searchable suggestion dropdown -->
        <div ref="selectorRef" class="tag-combobox position-relative">
            <div class="input-group input-group-sm">
                <input
                    v-model="newTagInput"
                    type="text"
                    class="form-control"
                    placeholder="ট্যাগ খুঁজুন বা নতুন লিখে Enter চাপুন..."
                    autocomplete="off"
                    @focus="openDropdown"
                    @input="handleInput"
                    @keydown.enter.prevent="onEnter"
                    @keydown.down.prevent="moveHighlight(1)"
                    @keydown.up.prevent="moveHighlight(-1)"
                    @keydown.esc="closeDropdown"
                />
                <button
                    type="button"
                    class="btn btn-outline-primary"
                    :disabled="!newTagInput.trim() || isSaving"
                    @click="addTag(newTagInput); closeDropdown()"
                >
                    যুক্ত করুন
                </button>
            </div>

            <!-- Searchable Suggestions Dropdown -->
            <ul
                v-if="isDropdownOpen && dropdownOptions.length > 0"
                class="tag-dropdown list-unstyled mb-0"
            >
                <li
                    v-for="(option, index) in dropdownOptions"
                    :key="(option.isNew ? 'new:' : 'tag:') + option.name"
                    class="tag-dropdown-item"
                    :class="{ 'is-active': index === highlightedIndex, 'is-new': option.isNew }"
                    @mousemove="highlightedIndex = index"
                    @click="selectOption(option)"
                >
                    <i class="bi me-1" :class="option.isNew ? 'bi-plus-circle' : 'bi-tag'"></i>
                    <template v-if="option.isNew">
                        নতুন ট্যাগ তৈরি করুন:
                        <strong class="ms-1">{{ option.name }}</strong>
                    </template>
                    <template v-else>{{ option.name }}</template>
                </li>
            </ul>
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

.tag-dropdown {
    position: absolute;
    top: calc(100% + 0.25rem);
    left: 0;
    right: 0;
    z-index: 1055;
    max-height: 220px;
    overflow-y: auto;
    padding: 0.25rem;
    background: var(--surface-card, #fff);
    border: 1px solid var(--border-default, #dde5e0);
    border-radius: var(--radius-control, 0.95rem);
    box-shadow: 0 12px 28px -12px rgba(30, 122, 92, 0.25);
}

.tag-dropdown-item {
    display: flex;
    align-items: center;
    padding: 0.4rem 0.6rem;
    border-radius: 0.6rem;
    font-size: 0.85rem;
    color: var(--text-primary, #1c211e);
    cursor: pointer;
}

.tag-dropdown-item.is-active {
    background: var(--brand-soft, #e4f3eb);
    color: var(--brand-primary, #1e7a5c);
}

.tag-dropdown-item.is-new {
    font-weight: 600;
    color: var(--brand-primary, #1e7a5c);
    border-top: 1px dashed var(--border-default, #dde5e0);
}
</style>

