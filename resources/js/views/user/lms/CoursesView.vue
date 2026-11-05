<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useLms } from '@/composables/useLms';
import { useNotification } from '@/composables/useNotification';
import { useConfirm } from '@/composables/useConfirm';

const router = useRouter();
const { courses, isLoading, isSaving, fetchCourses, createCourse, deleteCourse } = useLms();
const { success, error } = useNotification();
const { confirm } = useConfirm();

const showModal = ref(false);
const title = ref('');
const description = ref('');
const visibility = ref('private');

const openCreateModal = () => {
    title.value = '';
    description.value = '';
    visibility.value = 'private';
    showModal.value = true;
};

const handleCreateCourse = async () => {
    if (!title.value.trim()) return;
    try {
        const res = await createCourse({
            title: title.value.trim(),
            description: description.value.trim(),
            visibility: visibility.value,
        });
        success('কোর্স সফলভাবে তৈরি হয়েছে।');
        showModal.value = false;
        router.push(`/courses/${res.data.id}/builder`);
    } catch (err) {
        error('কোর্স তৈরি করতে সমস্যা হয়েছে।');
    }
};

const handleDelete = async (course) => {
    const isConfirmed = await confirm({
        title: 'কোর্স মুছে ফেলুন',
        message: `"${course.title}" কোর্সটি এবং এর অন্তর্গত সকল চ্যাপ্টার ও লেসন স্থায়ীভাবে মুছে যাবে। এটি ফিরিয়ে আনা যাবে না।`,
        confirmLabel: 'হ্যাঁ, কোর্স মুছুন',
        cancelLabel: 'বাতিল',
    });

    if (!isConfirmed) return;

    try {
        await deleteCourse(course.id);
        success('কোর্স সফলভাবে মুছে ফেলা হয়েছে।');
    } catch (e) {
        error('কোর্স মুছতে সমস্যা হয়েছে।');
    }
};

onMounted(() => {
    fetchCourses();
});
</script>

<template>
    <div class="courses-page py-4">
        <div class="container">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h2 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-mortarboard text-primary me-2"></i>ব্যক্তিগত LMS ও কোর্সসমূহ
                    </h2>
                    <p class="text-secondary small mb-0">
                        আপনার সংরক্ষিত ইউটিউব ভিডিও ও নোটসমূহকে কোর্স, চ্যাপ্টার ও লেসন আকারে সুসংগঠিত করুন।
                    </p>
                </div>
                <div class="d-flex gap-2 mt-3 mt-sm-0">
                    <button class="btn btn-primary btn-sm px-3" @click="openCreateModal">
                        <i class="bi bi-plus-circle me-1"></i> নতুন কোর্স তৈরি করুন
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">কোর্সসমূহ লোড হচ্ছে...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="courses.length === 0" class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
                <i class="bi bi-mortarboard text-muted fs-1 mb-2"></i>
                <h5 class="fw-bold text-dark">এখনো কোনো কোর্স তৈরি করা হয়নি</h5>
                <p class="text-secondary small mb-3 max-w-400 mx-auto">
                    আপনার সংরক্ষিত ইউটিউব ভিডিওগুলোকে সম্পর্কিত বিষয়ে সাজিয়ে নিজের মতো করে কোর্স তৈরি করুন।
                </p>
                <div>
                    <button class="btn btn-primary btn-sm px-4" @click="openCreateModal">
                        <i class="bi bi-plus-circle me-1"></i> প্রথম কোর্স তৈরি করুন
                    </button>
                </div>
            </div>

            <!-- Course Cards Grid -->
            <div v-else class="row g-4">
                <div v-for="course in courses" :key="course.id" class="col-md-6 col-lg-4">
                    <div class="card card-hover h-100 overflow-hidden d-flex flex-column">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge" :class="course.visibility === 'public' ? 'bg-success-subtle text-success' : 'bg-light text-secondary border'">
                                    <i class="bi" :class="course.visibility === 'public' ? 'bi-globe2' : 'bi-lock'"></i>
                                    {{ course.visibility === 'public' ? 'Public Course' : 'Private' }}
                                </span>

                                <div class="dropdown">
                                    <button class="btn btn-link btn-sm text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                                        <li>
                                            <router-link :to="`/courses/${course.id}/builder`" class="dropdown-item">
                                                <i class="bi bi-pencil me-2 text-primary"></i> এডিট ও সাজান
                                            </router-link>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger" @click="handleDelete(course)">
                                                <i class="bi bi-trash me-2"></i> কোর্স মুছুন
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <h5 class="fw-bold text-dark mb-2">
                                {{ course.title }}
                            </h5>

                            <p class="text-secondary small line-clamp-2 mb-3 flex-grow-1">
                                {{ course.description || 'কোনো বিবরণ যোগ করা হয়নি।' }}
                            </p>

                            <!-- Course Stats -->
                            <div class="d-flex align-items-center gap-3 smaller text-muted mb-3 py-2 border-top border-bottom">
                                <span><i class="bi bi-folder2-open text-primary me-1"></i> {{ course.chapters_count }} চ্যাপ্টার</span>
                                <span><i class="bi bi-file-earmark-play text-info me-1"></i> {{ course.lessons_count }} লেসন</span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center smaller text-muted mb-1">
                                    <span>লার্নিং অগ্রগতি</span>
                                    <span class="fw-semibold text-primary">{{ course.progress_percentage }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div
                                        class="progress-bar bg-primary"
                                        role="progressbar"
                                        :style="{ width: `${course.progress_percentage}%` }"
                                        :aria-valuenow="course.progress_percentage"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    ></div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex gap-2 pt-2">
                                <router-link :to="`/courses/${course.id}/builder`" class="btn btn-outline-primary btn-sm flex-fill">
                                    <i class="bi bi-diagram-3 me-1"></i> বিল্ডার
                                </router-link>
                                <router-link
                                    v-if="course.chapters && course.chapters.length > 0 && course.chapters[0].lessons && course.chapters[0].lessons.length > 0"
                                    :to="`/lessons/${course.chapters[0].lessons[0].id}`"
                                    class="btn btn-primary btn-sm flex-fill"
                                >
                                    <i class="bi bi-play-circle me-1"></i> শুরু করুন
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Course Modal -->
        <div v-if="showModal" class="modal-backdrop fade show"></div>
        <div v-if="showModal" class="modal fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom py-3">
                        <h5 class="modal-title fw-bold text-dark mb-0">
                            <i class="bi bi-mortarboard text-primary me-2"></i>নতুন কোর্স তৈরি করুন
                        </h5>
                        <button type="button" class="btn-close" :disabled="isSaving" @click="showModal = false"></button>
                    </div>
                    <form @submit.prevent="handleCreateCourse">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">
                                    কোর্সের শিরোনাম <span class="text-danger">*</span>
                                </label>
                                <input
                                    v-model="title"
                                    type="text"
                                    class="form-control"
                                    placeholder="উদাঃ আধুনিক ফুল-স্ট্যাক সফটওয়্যার ইঞ্জিনিয়ারিং"
                                    required
                                    autofocus
                                />
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">বিবরণ (ঐচ্ছিক)</label>
                                <textarea
                                    v-model="description"
                                    rows="3"
                                    class="form-control"
                                    placeholder="এই কোর্সে কী কী জ্ঞান ও বিষয়বস্তু সংরক্ষণ করা হবে..."
                                ></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">ভিজিবিলিটি</label>
                                <select v-model="visibility" class="form-select form-select-sm">
                                    <option value="private">Private (শুধুমাত্র আমার জন্য)</option>
                                    <option value="public">Public (পাবলিক শেয়ারযোগ্য)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-top py-3 bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="isSaving" @click="showModal = false">বাতিল</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4" :disabled="isSaving || !title.trim()">
                                <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                                কোর্স তৈরি করুন
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
    font-size: 0.75rem;
}
.max-w-400 {
    max-width: 400px;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

