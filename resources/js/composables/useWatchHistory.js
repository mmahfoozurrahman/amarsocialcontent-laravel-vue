import { ref } from 'vue';
import api from '@/services/api';
import { useNotification } from '@/composables/useNotification';

export function useWatchHistory() {
    const { success, error } = useNotification();

    const items = ref([]);
    const channels = ref([]);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0,
    });
    const latestImport = ref(null);
    const serverFileInfo = ref(null);
    const isCheckingServerFile = ref(false);

    const isUploading = ref(false);
    const isLoadingItems = ref(false);
    const isImporting = ref(false);

    /**
     * Check if a watch-history file exists on server.
     */
    const checkServerFile = async () => {
        isCheckingServerFile.value = true;
        try {
            const res = await api.get('/user/watch-history/server-file-status');
            if (res.data?.data?.has_server_file) {
                serverFileInfo.value = res.data.data.file_info;
            } else {
                serverFileInfo.value = null;
            }
        } catch (err) {
            serverFileInfo.value = null;
        } finally {
            isCheckingServerFile.value = false;
        }
    };

    /**
     * Load watch-history directly from server public folder.
     */
    const loadServerFile = async (fileName = 'watch-history.json') => {
        isUploading.value = true;
        try {
            const res = await api.post('/user/watch-history/load-server-file', { file_name: fileName });
            success(res.data.message || 'ফাইল সফলভাবে প্রসেস করা হয়েছে!');
            latestImport.value = res.data.data;
            await fetchItems({ import_id: res.data.data.import_id });
            await fetchChannels(res.data.data.import_id);
            return res.data.data;
        } catch (err) {
            const msg = err.response?.data?.message || 'ফাইল প্রসেস করতে সমস্যা হয়েছে।';
            error(msg);
            throw err;
        } finally {
            isUploading.value = false;
        }
    };

    /**
     * Upload and parse Google Takeout watch-history file.
     */
    const uploadFile = async (file) => {
        isUploading.value = true;
        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await api.post('/user/watch-history/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            success(res.data.message || 'ফাইল সফলভাবে প্রসেস করা হয়েছে!');
            latestImport.value = res.data.data;
            await fetchItems({ import_id: res.data.data.import_id });
            await fetchChannels(res.data.data.import_id);
            return res.data.data;
        } catch (err) {
            const msg = err.response?.data?.message || 'ফাইল আপলোড ও প্রসেস করতে সমস্যা হয়েছে।';
            error(msg);
            throw err;
        } finally {
            isUploading.value = false;
        }
    };

    /**
     * Fetch list of watch history items with filters.
     */
    const fetchItems = async (params = {}) => {
        isLoadingItems.value = true;
        try {
            const res = await api.get('/user/watch-history/items', { params });
            items.value = res.data.data;
            pagination.value = res.data.pagination;
        } catch (err) {
            error('হিস্ট্রি তালিকা লোড করতে ব্যর্থ হয়েছে।');
        } finally {
            isLoadingItems.value = false;
        }
    };

    /**
     * Fetch channel counts for filter pills.
     */
    const fetchChannels = async (importId = null) => {
        try {
            const params = importId ? { import_id: importId } : {};
            const res = await api.get('/user/watch-history/channels', { params });
            channels.value = res.data.data;
        } catch (err) {
            // Silently handle
        }
    };

    /**
     * Bulk import selected items into library.
     */
    const batchImport = async (itemIds) => {
        if (!itemIds || itemIds.length === 0) return;
        isImporting.value = true;
        try {
            const res = await api.post('/user/watch-history/import', { item_ids: itemIds });
            success(res.data.message || 'ভিডিও এআই প্রসেসিংয়ের জন্য কিউতে পাঠানো হয়েছে!');
            return res.data.data;
        } catch (err) {
            const msg = err.response?.data?.message || 'ভিডিও ইমপোর্ট করতে সমস্যা হয়েছে।';
            error(msg);
            throw err;
        } finally {
            isImporting.value = false;
        }
    };

    return {
        items,
        channels,
        pagination,
        latestImport,
        serverFileInfo,
        isCheckingServerFile,
        isUploading,
        isLoadingItems,
        isImporting,
        checkServerFile,
        loadServerFile,
        uploadFile,
        fetchItems,
        fetchChannels,
        batchImport,
    };
}

