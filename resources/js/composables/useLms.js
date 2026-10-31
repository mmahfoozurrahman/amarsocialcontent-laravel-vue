import { ref } from 'vue';
import api from '../services/api';

const courses = ref([]);
const currentCourse = ref(null);
const currentLesson = ref(null);
const unassignedArticles = ref([]);
const isLoading = ref(false);
const isSaving = ref(false);

export function useLms() {
    const fetchCourses = async () => {
        isLoading.value = true;
        try {
            const res = await api.get('/user/courses');
            courses.value = res.data.data;
            return res.data;
        } finally {
            isLoading.value = false;
        }
    };

    const fetchCourse = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.get(`/user/courses/${id}`);
            currentCourse.value = res.data.data;
            return currentCourse.value;
        } finally {
            isLoading.value = false;
        }
    };

    const createCourse = async (data) => {
        isSaving.value = true;
        try {
            const res = await api.post('/user/courses', data);
            await fetchCourses();
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const updateCourse = async (id, data) => {
        isSaving.value = true;
        try {
            const res = await api.put(`/user/courses/${id}`, data);
            if (currentCourse.value && currentCourse.value.id === id) {
                currentCourse.value = { ...currentCourse.value, ...res.data.data };
            }
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const deleteCourse = async (id) => {
        try {
            const res = await api.delete(`/user/courses/${id}`);
            courses.value = courses.value.filter((c) => c.id !== id);
            return res.data;
        } catch (e) {
            throw e;
        }
    };

    // Chapter Operations
    const createChapter = async (courseId, data) => {
        isSaving.value = true;
        try {
            const res = await api.post(`/user/courses/${courseId}/chapters`, data);
            await fetchCourse(courseId);
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const deleteChapter = async (courseId, chapterId) => {
        try {
            const res = await api.delete(`/user/chapters/${chapterId}`);
            await fetchCourse(courseId);
            return res.data;
        } catch (e) {
            throw e;
        }
    };

    // Lesson Operations
    const createLesson = async (courseId, chapterId, data) => {
        isSaving.value = true;
        try {
            const res = await api.post(`/user/chapters/${chapterId}/lessons`, data);
            await fetchCourse(courseId);
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const fetchLesson = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.get(`/user/lessons/${id}`);
            currentLesson.value = res.data.data;
            return currentLesson.value;
        } finally {
            isLoading.value = false;
        }
    };

    const updateLesson = async (id, data) => {
        isSaving.value = true;
        try {
            const res = await api.put(`/user/lessons/${id}`, data);
            if (currentLesson.value && currentLesson.value.id === id) {
                currentLesson.value = { ...currentLesson.value, ...res.data.data };
            }
            return res.data;
        } finally {
            isSaving.value = false;
        }
    };

    const deleteLesson = async (courseId, lessonId) => {
        try {
            const res = await api.delete(`/user/lessons/${lessonId}`);
            await fetchCourse(courseId);
            return res.data;
        } catch (e) {
            throw e;
        }
    };

    const toggleLessonComplete = async (lessonId, isCompleted) => {
        try {
            const res = await api.put(`/user/lessons/${lessonId}`, {
                is_completed: isCompleted,
            });
            if (currentLesson.value && currentLesson.value.id === lessonId) {
                currentLesson.value.is_completed = res.data.data.is_completed;
            }
            return res.data;
        } catch (e) {
            throw e;
        }
    };

    const fetchUnassignedArticles = async () => {
        try {
            const res = await api.get('/user/unassigned-articles');
            unassignedArticles.value = res.data.data;
            return unassignedArticles.value;
        } catch (e) {
            return [];
        }
    };

    return {
        courses,
        currentCourse,
        currentLesson,
        unassignedArticles,
        isLoading,
        isSaving,
        fetchCourses,
        fetchCourse,
        createCourse,
        updateCourse,
        deleteCourse,
        createChapter,
        deleteChapter,
        createLesson,
        fetchLesson,
        updateLesson,
        deleteLesson,
        toggleLessonComplete,
        fetchUnassignedArticles,
    };
}

