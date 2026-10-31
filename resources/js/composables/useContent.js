import { ref } from 'vue';
import api from '../services/api';

const articles = ref([]);
const pagination = ref({});
const isLoading = ref(false);
const isSubmitting = ref(false);

export function useContent() {
    const fetchArticles = async (page = 1, filters = {}) => {
        isLoading.value = true;
        try {
            const response = await api.get('/user/articles', {
                params: { page, ...filters },
            });
            articles.value = response.data.data;
            pagination.value = response.data.meta || {};
            return response.data;
        } finally {
            isLoading.value = false;
        }
    };

    const fetchArticle = async (id) => {
        isLoading.value = true;
        try {
            const response = await api.get(`/user/articles/${id}`);
            return response.data.data;
        } finally {
            isLoading.value = false;
        }
    };

    const submitYouTubeUrl = async (youtubeUrl, manualTranscript = null) => {
        isSubmitting.value = true;
        try {
            const response = await api.post('/user/articles', {
                youtube_url: youtubeUrl,
                manual_transcript: manualTranscript,
            });
            await fetchArticles();
            return response.data;
        } finally {
            isSubmitting.value = false;
        }
    };

    const submitManualTranscript = async (id, transcript) => {
        isSubmitting.value = true;
        try {
            const response = await api.post(`/user/articles/${id}/manual-transcript`, {
                transcript,
            });
            await fetchArticles();
            return response.data;
        } finally {
            isSubmitting.value = false;
        }
    };

    const retryContent = async (id) => {
        try {
            const response = await api.post(`/user/articles/${id}/retry`);
            await fetchArticles();
            return response.data;
        } catch (e) {
            throw e;
        }
    };

    const deleteContent = async (id) => {
        try {
            const response = await api.delete(`/user/articles/${id}`);
            articles.value = articles.value.filter((a) => a.id !== id);
            return response.data;
        } catch (e) {
            throw e;
        }
    };

    return {
        articles,
        pagination,
        isLoading,
        isSubmitting,
        fetchArticles,
        fetchArticle,
        submitYouTubeUrl,
        submitManualTranscript,
        retryContent,
        deleteContent,
    };
}

