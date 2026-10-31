import { ref } from 'vue';
import api from '@/services/api';
import { useNotification } from '@/composables/useNotification';
import { useAuth } from '@/composables/useAuth';

export function useProfile() {
    const { success, error } = useNotification();
    const { user } = useAuth();

    const profile = ref(null);
    const aiSettings = ref({
        primary_provider: 'groq',
        groq_configured: false,
        groq_masked_key: null,
        groq_model: 'openai/gpt-oss-120b',
        gemini_configured: false,
        gemini_masked_key: null,
        gemini_model: 'gemini-1.5-flash',
        has_own_key: false,
        is_ready: false,
    });

    const isLoading = ref(true);
    const isSaving = ref(false);
    const isTesting = ref(false);

    const fetchProfile = async () => {
        isLoading.value = true;
        try {
            const res = await api.get('/user/profile');
            profile.value = res.data.data.user;
            aiSettings.value = res.data.data.ai_settings;
        } catch (err) {
            error('প্রোফাইল তথ্য লোড করতে সমস্যা হয়েছে।');
        } finally {
            isLoading.value = false;
        }
    };

    const updateProfileName = async (name) => {
        isSaving.value = true;
        try {
            const res = await api.put('/user/profile', { name });
            profile.value.name = name;
            if (user.value) {
                user.value.name = name;
                localStorage.setItem('amar_auth_user', JSON.stringify(user.value));
            }
            success(res.data.message || 'প্রোফাইল নাম আপডেট হয়েছে!');
            return true;
        } catch (err) {
            error(err.response?.data?.message || 'প্রোফাইল আপডেট করতে সমস্যা হয়েছে।');
            return false;
        } finally {
            isSaving.value = false;
        }
    };

    const updateAiSettings = async (payload) => {
        isSaving.value = true;
        try {
            const res = await api.put('/user/profile/ai-settings', payload);
            aiSettings.value = {
                ...aiSettings.value,
                ...res.data.data,
            };
            success(res.data.message || 'এআই সেটিংস সফলভাবে সংরক্ষিত হয়েছে!');
            return true;
        } catch (err) {
            error(err.response?.data?.message || 'এআই সেটিংস সংরক্ষণ করতে ব্যর্থ হয়েছে।');
            return false;
        } finally {
            isSaving.value = false;
        }
    };

    const testAiKey = async (provider, apiKey = null) => {
        isTesting.value = true;
        try {
            const res = await api.post('/user/profile/test-ai', {
                provider,
                api_key: apiKey,
            });
            success(res.data.message || 'এপিআই কী সক্রিয় ও প্রস্তুত!');
            return { ok: true, message: res.data.message };
        } catch (err) {
            const msg = err.response?.data?.message || 'কানেকশন টেস্ট ব্যর্থ হয়েছে। কী যাচাই করুন।';
            error(msg);
            return { ok: false, message: msg };
        } finally {
            isTesting.value = false;
        }
    };

    return {
        profile,
        aiSettings,
        isLoading,
        isSaving,
        isTesting,
        fetchProfile,
        updateProfileName,
        updateAiSettings,
        testAiKey,
    };
}

