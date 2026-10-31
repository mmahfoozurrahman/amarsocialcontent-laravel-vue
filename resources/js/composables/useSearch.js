import { ref } from 'vue';
import api from '../services/api';

export function useSearch() {
    const query = ref('');
    const searchType = ref('hybrid'); // hybrid, semantic, fulltext
    const contentType = ref('all');   // all, video, short
    const results = ref([]);
    const meta = ref({ query: '', total: 0, search_type: 'hybrid' });
    const isLoading = ref(false);
    const searchHistory = ref([]);

    const executeSearch = async (newQuery = null) => {
        if (newQuery !== null) {
            query.value = newQuery;
        }

        const trimmed = query.value.trim();
        if (!trimmed) {
            results.value = [];
            meta.value = { query: '', total: 0, search_type: searchType.value };
            return;
        }

        isLoading.value = true;
        try {
            const response = await api.get('/user/search', {
                params: {
                    q: trimmed,
                    type: searchType.value,
                    content_type: contentType.value,
                },
            });

            if (response.data.success) {
                results.value = response.data.data;
                meta.value = response.data.meta;
            }
        } catch (err) {
            results.value = [];
        } finally {
            isLoading.value = false;
        }
    };

    const fetchHistory = async () => {
        try {
            const response = await api.get('/user/search/history');
            if (response.data.success) {
                searchHistory.value = response.data.data;
            }
        } catch {
            searchHistory.value = [];
        }
    };

    const clearHistory = async () => {
        try {
            await api.delete('/user/search/history');
            searchHistory.value = [];
        } catch (e) {
            // Ignore
        }
    };

    return {
        query,
        searchType,
        contentType,
        results,
        meta,
        isLoading,
        searchHistory,
        executeSearch,
        fetchHistory,
        clearHistory,
    };
}

