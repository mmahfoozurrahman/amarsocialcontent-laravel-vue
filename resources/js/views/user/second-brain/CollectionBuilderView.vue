<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSecondBrain } from '@/composables/useSecondBrain';
import { useNotification } from '@/composables/useNotification';
import { useConfirm } from '@/composables/useConfirm';

const route = useRoute();
const router = useRouter();
const collectionId = computed(() => Number(route.params.id));

const {
    currentCollection,
    unassignedArticles,
    isLoading,
    isSaving,
    fetchCollection,
    createSection,
    deleteSection,
    createNote,
    deleteNote,
    fetchUnassignedArticles,
} = useSecondBrain();

const { success, error } = useNotification();
const { confirm } = useConfirm();

// Modal States
const showSectionModal = ref(false);
const sectionTitle = ref('');
const sectionDesc = ref('');

const showNoteModal = ref(false);
const activeSectionId = ref(null);
const noteTitle = ref('');
const noteContent = ref('');
const selectedArticleId = ref('');

// Open Add Section
const openAddSection = () => {
    sectionTitle.value = '';
    sectionDesc.value = '';
    showSectionModal.value = true;
};

// Handle Add Section
const handleAddSection = async () => {
    if (!sectionTitle.value.trim()) return;
    try {
        await createSection(collectionId.value, {
            title: sectionTitle.value.trim(),
            description: sectionDesc.value.trim(),
        });
        success('সেকশনস সফলভাবে তৈরি হয়েছে।');
        showSectionModal.value = false;
    } catch (err) {
        error('সেকশনস তৈরিতে সমস্যা হয়েছে।');
    }
};

// Open Add Note
const openAddNote = (sectionId, preselectedArticle = null) => {
    activeSectionId.value = sectionId;
    if (preselectedArticle) {
        noteTitle.value = preselectedArticle.bengali_title || preselectedArticle.title;
        selectedArticleId.value = preselectedArticle.id;
    } else {
        noteTitle.value = '';
        selectedArticleId.value = '';
    }
    noteContent.value = '';
    showNoteModal.value = true;
};

// Handle Add Note
const handleAddNote = async () => {
    if (!noteTitle.value.trim()) return;
    try {
        await createNote(collectionId.value, activeSectionId.value, {
            title: noteTitle.value.trim(),
            aggregated_content_id: selectedArticleId.value ? Number(selectedArticleId.value) : null,
            content: noteContent.value.trim() || null,
        });
        success('নোটস সফলভাবে যুক্ত হয়েছে।');
        showNoteModal.value = false;
        fetchUnassignedArticles();
    } catch (err) {
        error(err.response?.data?.message || 'নোটস তৈরিতে সমস্যা হয়েছে।');
    }
};

// Delete Section
const handleDeleteSection = async (section) => {
    const isConfirmed = await confirm({
        title: 'সেকশনস মুছে ফেলুন',
        message: `"${section.title}" সেকশনসটি এবং এর ভেতরের সকল নোটস স্থায়ীভাবে মুছে যাবে।`,
        confirmLabel: 'হ্যাঁ, সেকশনস মুছুন',
        cancelLabel: 'বাতিল',
    });

    if (!isConfirmed) return;

    try {
        await deleteSection(collectionId.value, section.id);
        success('সেকশনস মুছে ফেলা হয়েছে।');
        fetchUnassignedArticles();
    } catch (e) {
        error('সেকশনস মুছতে সমস্যা হয়েছে।');
    }
};

// Delete Note
const handleDeleteNote = async (note) => {
    const isConfirmed = await confirm({
        title: 'নোটস মুছে ফেলুন',
        message: `"${note.title}" নোটসটি কালেকশনস থেকে স্থায়ীভাবে মুছে যাবে। মূল আর্টিকেলটি আপনার লাইব্রেরিতে অক্ষত থাকবে।`,
        confirmLabel: 'হ্যাঁ, নোটস মুছুন',
        cancelLabel: 'বাতিল',
    });

    if (!isConfirmed) return;

    try {
        await deleteNote(collectionId.value, note.id);
        success('নোটস মুছে ফেলা হয়েছে।');
        fetchUnassignedArticles();
    } catch (e) {
        error('নোটস মুছতে সমস্যা হয়েছে।');
    }
};

onMounted(async () => {
    await fetchCollection(collectionId.value);
    await fetchUnassignedArticles();
});
</script>

<template>
    <div class="collection-builder-page py-4">
        <div class="container">
            <!-- Loading -->
            <div v-if="isLoading && !currentCollection" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">কালেকশনস লোড হচ্ছে...</p>
            </div>

            <div v-else-if="currentCollection" class="row g-4">
                <!-- Left: Collection Structure & Section Accordions -->
                <div class="col-lg-8">
                    <!-- Header -->
                    <div class="card border-0 shadow-sm p-4 bg-white rounded-4 mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <router-link to="/collections" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> সকল কালেকশনসে ফিরে যান
                            </router-link>
                            <span class="badge" :class="currentCollection.visibility === 'public' ? 'bg-success-subtle text-success' : 'bg-light text-secondary border'">
                                {{ currentCollection.visibility === 'public' ? 'Public' : 'Private' }}
                            </span>
                        </div>

                        <h3 class="fw-bold text-dark mt-2 mb-1">{{ currentCollection.title }}</h3>
                        <p class="text-secondary small mb-3">{{ currentCollection.description || 'কোনো বিবরণ দেওয়া হয়নি।' }}</p>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm px-3" @click="openAddSection">
                                <i class="bi bi-plus-circle me-1"></i> নতুন সেকশনস যুক্ত করুন
                            </button>
                        </div>
                    </div>

                    <!-- Sections Listing -->
                    <div v-if="!currentCollection.sections || currentCollection.sections.length === 0" class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
                        <i class="bi bi-folder-plus text-muted fs-1 mb-2"></i>
                        <h6 class="fw-bold text-dark">এই কালেকশনসে কোনো সেকশনস নেই</h6>
                        <p class="text-secondary small mb-3">কালেকশনসের জ্ঞান সাজাতে সেকশনস তৈরি করে তার মধ্যে নোটস ও আর্টিকেল যুক্ত করুন।</p>
                        <div>
                            <button class="btn btn-outline-primary btn-sm px-3" @click="openAddSection">
                                <i class="bi bi-plus-lg me-1"></i> প্রথম সেকশনস তৈরি করুন
                            </button>
                        </div>
                    </div>

                    <div v-else class="sections-container d-flex flex-column gap-3">
                        <div
                            v-for="(section, cIndex) in currentCollection.sections"
                            :key="section.id"
                            class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white"
                        >
                            <!-- Section Header -->
                            <div class="card-header bg-light border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary me-2">সেকশনস {{ cIndex + 1 }}</span>
                                    <strong class="text-dark">{{ section.title }}</strong>
                                    <p v-if="section.description" class="smaller text-muted mb-0 mt-1">{{ section.description }}</p>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <button class="btn btn-primary btn-sm py-1 px-2" @click="openAddNote(section.id)">
                                        <i class="bi bi-plus-lg me-1"></i> নোটস যোগ করুন
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm py-1 px-2" title="সেকশনস মুছুন" @click="handleDeleteSection(section)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Notes List inside Section -->
                            <div class="card-body p-0">
                                <div v-if="!section.notes || section.notes.length === 0" class="text-center py-4 text-muted smaller">
                                    এই সেকশনসে এখনো কোনো নোটস যুক্ত করা হয়নি। ডানপাশের লাইব্রেরি থেকে যুক্ত করুন।
                                </div>

                                <ul v-else class="list-group list-group-flush">
                                    <li
                                        v-for="(note, lIndex) in section.notes"
                                        :key="note.id"
                                        class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 hover-bg transition"
                                    >
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge rounded-circle bg-light text-secondary border p-2 smaller">
                                                {{ lIndex + 1 }}
                                            </span>
                                            <div>
                                                <div class="fw-semibold text-dark small">
                                                    {{ note.title }}
                                                </div>
                                                <div class="smaller text-muted d-flex align-items-center gap-2 mt-1">
                                                    <span v-if="note.is_linked_to_article" class="text-primary">
                                                        <i class="bi bi-youtube me-1"></i> লিঙ্কড ভিডিও আর্টিকেল
                                                    </span>
                                                    <span v-else class="text-secondary">
                                                        <i class="bi bi-journal-text me-1"></i> কাস্টম নোটস
                                                    </span>
                                                    <span v-if="note.is_completed" class="badge bg-success-subtle text-success py-0 px-1">
                                                        <i class="bi bi-check2"></i> সম্পন্ন
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <router-link :to="`/notes/${note.id}`" class="btn btn-outline-primary btn-sm py-1 px-2">
                                                <i class="bi bi-book me-1"></i> পড়ুন
                                            </router-link>
                                            <button class="btn btn-outline-danger btn-sm py-1 px-2" @click="handleDeleteNote(note)">
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
                            যেসব ভিডিও আর্টিকেল এখনো কোনো কালেকশনসে অন্তর্ভুক্ত করা হয়নি, সেগুলোকে এক ক্লিকে সেকশনসে নোটস হিসেবে যুক্ত করুন:
                        </p>

                        <div v-if="unassignedArticles.length === 0" class="text-center py-4 text-muted smaller">
                            <i class="bi bi-check2-all text-success fs-3 d-block mb-1"></i>
                            সবগুলো আর্টিকেল কোনো না কোনো কালেকশনসে অন্তর্ভুক্ত রয়েছে!
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

                                    <!-- Quick Assign Dropdown if sections exist -->
                                    <div v-if="currentCollection.sections && currentCollection.sections.length > 0" class="dropdown">
                                        <button class="btn btn-primary btn-sm py-0 px-2 smaller dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            + সেকশনসে যোগ করুন
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                            <li v-for="ch in currentCollection.sections" :key="ch.id">
                                                <button class="dropdown-item" @click="openAddNote(ch.id, item)">
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

        <!-- Add Section Modal -->
        <div v-if="showSectionModal" class="modal-backdrop fade show"></div>
        <div v-if="showSectionModal" class="modal fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0">নতুন সেকশনস তৈরি করুন</h5>
                        <button type="button" class="btn-close" @click="showSectionModal = false"></button>
                    </div>
                    <form @submit.prevent="handleAddSection">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">সেকশনসের শিরোনাম *</label>
                                <input v-model="sectionTitle" type="text" class="form-control" placeholder="উদাঃ পার্ট ১: ডেটাবেজ অপ্টিমাইজেশন" required autofocus />
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">সংক্ষিপ্ত বিবরণ (ঐচ্ছিক)</label>
                                <textarea v-model="sectionDesc" rows="2" class="form-control" placeholder="সেকশনসের মূল বিষয়..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-top py-3 bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="showSectionModal = false">বাতিল</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4" :disabled="isSaving || !sectionTitle.trim()">
                                সেকশনস তৈরি করুন
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Note Modal -->
        <div v-if="showNoteModal" class="modal-backdrop fade show"></div>
        <div v-if="showNoteModal" class="modal fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0">সেকশনসে নোটস যুক্ত করুন</h5>
                        <button type="button" class="btn-close" @click="showNoteModal = false"></button>
                    </div>
                    <form @submit.prevent="handleAddNote">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">নোটসের শিরোনাম *</label>
                                <input v-model="noteTitle" type="text" class="form-control" placeholder="উদাঃ মাইগ্রেশন ও ইনডেক্সিং গাইড" required autofocus />
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
                                <label class="form-label small fw-semibold text-secondary">কাস্টম নোটস নোটস / বক্তব্য (ঐচ্ছিক)</label>
                                <textarea
                                    v-model="noteContent"
                                    rows="5"
                                    class="form-control"
                                    placeholder="যদি ভিডিও লিংক না থাকে বা অতিরিক্ত নোট যোগ করতে চান তবে এখানে লিখুন..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-top py-3 bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="showNoteModal = false">বাতিল</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4" :disabled="isSaving || !noteTitle.trim()">
                                নোটস সংরক্ষণ করুন
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

