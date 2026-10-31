import { ref } from 'vue';
import api from '@/services/api';
import { useNotification } from '@/composables/useNotification';

export function useAdmin() {
    const { error } = useNotification();

    const stats = ref(null);
    const aiHealth = ref(null);
    const aiLogs = ref([]);
    const aiLogsPagination = ref({ current_page: 1, last_page: 1, total: 0 });
    const activityLogs = ref([]);
    const activityPagination = ref({ current_page: 1, last_page: 1, total: 0 });

    const isLoading = ref(true);

    const fetchStats = async () => {
        try {
            const res = await api.get('/admin/stats');
            stats.value = res.data.data;
        } catch (err) {
            error('অ্যাডমিন পরিসংখ্যান লোড করতে ব্যর্থ হয়েছে।');
        }
    };

    const fetchAIHealth = async () => {
        try {
            const res = await api.get('/admin/ai-health');
            aiHealth.value = res.data.data;
        } catch (err) {
            error('এআই হেলথ মেট্রিক্স লোড করতে ব্যর্থ হয়েছে।');
        }
    };

    const fetchAILogs = async (params = {}) => {
        try {
            const res = await api.get('/admin/ai-logs', { params });
            aiLogs.value = res.data.data;
            aiLogsPagination.value = res.data.pagination;
        } catch (err) {
            error('এআই প্রসেসিং লগ লোড করা যায়নি।');
        }
    };

    const fetchActivityLogs = async (params = {}) => {
        try {
            const res = await api.get('/admin/activity-logs', { params });
            activityLogs.value = res.data.data;
            activityPagination.value = res.data.pagination;
        } catch (err) {
            error('অ্যাক্টিভিটি লগ লোড করা যায়নি।');
        }
    };

    const loadAll = async () => {
        isLoading.value = true;
        try {
            await Promise.all([
                fetchStats(),
                fetchAIHealth(),
                fetchAILogs(),
                fetchActivityLogs(),
            ]);
        } finally {
            isLoading.value = false;
        }
    };

    return {
        stats,
        aiHealth,
        aiLogs,
        aiLogsPagination,
        activityLogs,
        activityPagination,
        isLoading,
        fetchStats,
        fetchAIHealth,
        fetchAILogs,
        fetchActivityLogs,
        loadAll,
    };
}

