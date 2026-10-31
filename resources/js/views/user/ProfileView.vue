<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useProfile } from '@/composables/useProfile';

const route = useRoute();

const {
    profile,
    aiSettings,
    isLoading,
    isSaving,
    isTesting,
    fetchProfile,
    updateProfileName,
    updateAiSettings,
    testAiKey,
} = useProfile();

const activeTab = ref(route.query.tab === 'ai' ? 'ai' : 'profile');

// Form States
const name = ref('');
const nameError = ref('');

// AI Form State
const primaryProvider = ref('groq');
const groqApiKey = ref('');
const showGroqKey = ref(false);
const groqModel = ref('openai/gpt-oss-120b');
const clearGroqKey = ref(false);

const geminiApiKey = ref('');
const showGeminiKey = ref(false);
const geminiModel = ref('gemini-3-flash-preview');
const clearGeminiKey = ref(false);

const testingProvider = ref('');
const testFeedback = ref({});

const handleSaveProfile = async () => {
    nameError.value = '';
    if (!name.value.trim() || name.value.trim().length < 2) {
        nameError.value = 'অনুগ্রহ করে কমপক্ষে ২ অক্ষরের একটি বৈধ নাম লিখুন।';
        return;
    }
    await updateProfileName(name.value.trim());
};

const handleSaveAiSettings = async () => {
    const payload = {
        primary_provider: primaryProvider.value,
        groq_model: groqModel.value,
        gemini_model: geminiModel.value,
    };

    if (clearGroqKey.value) {
        payload.clear_groq = true;
    } else if (groqApiKey.value.trim()) {
        payload.groq_api_key = groqApiKey.value.trim();
    }

    if (clearGeminiKey.value) {
        payload.clear_gemini = true;
    } else if (geminiApiKey.value.trim()) {
        payload.gemini_api_key = geminiApiKey.value.trim();
    }

    const success = await updateAiSettings(payload);
    if (success) {
        groqApiKey.value = '';
        geminiApiKey.value = '';
        clearGroqKey.value = false;
        clearGeminiKey.value = false;
    }
};

const handleTestKey = async (provider) => {
    testingProvider.value = provider;
    testFeedback.value[provider] = null;

    const keyToTest = provider === 'groq' ? groqApiKey.value.trim() : geminiApiKey.value.trim();
    const result = await testAiKey(provider, keyToTest || null);
    testFeedback.value[provider] = result;
    testingProvider.value = '';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('bn-BD', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    } catch (e) {
        return dateStr;
    }
};

onMounted(async () => {
    await fetchProfile();
    if (profile.value) {
        name.value = profile.value.name;
    }
    if (aiSettings.value) {
        primaryProvider.value = aiSettings.value.primary_provider || 'groq';
        groqModel.value = aiSettings.value.groq_model || 'openai/gpt-oss-120b';
        geminiModel.value = aiSettings.value.gemini_model || 'gemini-3-flash-preview';
    }
});
</script>

<template>
    <div class="profile-settings-page bg-light min-vh-100 pb-5">
        <div class="container py-4">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="bi bi-gear-fill text-primary me-2"></i>অ্যাকাউন্ট ও এআই কনফিগারেশন
                    </h2>
                    <p class="text-secondary small mb-0">
                        আপনার ব্যক্তিগত প্রোফাইল তথ্য এবং নিজস্ব Groq ও Gemini API Key পরিচালনা করুন।
                    </p>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading && !profile" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">প্রোফাইল লোড হচ্ছে...</p>
            </div>

            <div v-else class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">
                    <!-- Nav Tabs Card -->
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                        <div class="card-header bg-white border-bottom p-3">
                            <ul class="nav nav-pills gap-2">
                                <li class="nav-item">
                                    <button
                                        type="button"
                                        class="nav-link rounded-pill px-4 py-2 small fw-semibold transition-all"
                                        :class="activeTab === 'profile' ? 'active bg-primary text-white shadow-xs' : 'text-secondary'"
                                        @click="activeTab = 'profile'"
                                    >
                                        <i class="bi bi-person-fill me-1"></i> ব্যক্তিগত প্রোফাইল
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                        type="button"
                                        class="nav-link rounded-pill px-4 py-2 small fw-semibold transition-all"
                                        :class="activeTab === 'ai' ? 'active bg-primary text-white shadow-xs' : 'text-secondary'"
                                        @click="activeTab = 'ai'"
                                    >
                                        <i class="bi bi-cpu-fill me-1"></i> এআই এপিআই কী ও মডেল
                                        <span v-if="!aiSettings?.has_own_key" class="badge bg-warning text-dark ms-1 smaller">
                                            কী প্রয়োজন
                                        </span>
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <!-- Tab 1: Personal Profile Info -->
                        <div v-if="activeTab === 'profile'" class="card-body p-4">
                            <form @submit.prevent="handleSaveProfile" novalidate>
                                <!-- Avatar & Email Summary -->
                                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4 border">
                                    <div class="avatar-box bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow-xs">
                                        {{ (profile?.name || 'U')[0].toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <h5 class="fw-bold text-dark mb-0 text-truncate">{{ profile?.name }}</h5>
                                        <div class="small text-muted d-flex align-items-center gap-1 mt-0.5">
                                            <i class="bi bi-envelope-fill"></i> {{ profile?.email }}
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill smaller ms-1">
                                                ভেরিফায়েড
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ms-auto text-end">
                                        <span class="badge" :class="profile?.role === 'admin' ? 'bg-danger' : 'bg-primary-subtle text-primary'">
                                            {{ profile?.role === 'admin' ? 'অ্যাডমিন' : 'ব্যবহারকারী' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Name Input -->
                                <div class="mb-4">
                                    <label for="userName" class="form-label small fw-semibold text-secondary">
                                        আপনার পূর্ণ নাম <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-person text-muted"></i>
                                        </span>
                                        <input
                                            id="userName"
                                            v-model="name"
                                            type="text"
                                            class="form-control border-start-0"
                                            :class="{ 'is-invalid': nameError }"
                                            placeholder="আপনার নাম লিখুন..."
                                            required
                                        />
                                    </div>
                                    <div v-if="nameError" class="invalid-feedback-custom">
                                        <i class="bi bi-exclamation-circle-fill"></i> {{ nameError }}
                                    </div>
                                </div>

                                <!-- Email (Readonly) -->
                                <div class="mb-4">
                                    <label class="form-label small fw-semibold text-secondary">
                                        নিবন্ধিত ইমেইল এড্রেস (অপরিবর্তনযোগ্য)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-lock text-muted"></i>
                                        </span>
                                        <input
                                            type="email"
                                            class="form-control border-start-0 bg-light"
                                            :value="profile?.email"
                                            disabled
                                        />
                                    </div>
                                    <div class="form-text smaller text-muted mt-1">
                                        নিরাপত্তার স্বার্থে ইমেইল ঠিকানা অপরিবর্তনযোগ্য।
                                    </div>
                                </div>

                                <!-- Member Since -->
                                <div class="mb-4 p-3 bg-light rounded-3 border smaller text-secondary d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-calendar-check me-1 text-primary"></i> যুক্ত হয়েছেন:</span>
                                    <strong>{{ formatDate(profile?.created_at) }}</strong>
                                </div>

                                <div class="text-end">
                                    <button
                                        type="submit"
                                        class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-xs"
                                        :disabled="isSaving"
                                    >
                                        <span v-if="isSaving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                        <i v-else class="bi bi-check2-circle me-1"></i>
                                        তথ্য সংরক্ষণ করুন
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Tab 2: AI Keys Setup (BYOK Model) -->
                        <div v-if="activeTab === 'ai'" class="card-body p-4">
                            <!-- Privacy & Security Guarantee Banner -->
                            <div class="alert alert-primary bg-primary-subtle border-0 rounded-4 p-3 mb-4">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-shield-lock-fill text-primary fs-4 mt-0.5"></i>
                                    <div>
                                        <div class="fw-bold text-dark small mb-1">
                                            পূর্ণ নিরাপত্তা ও ব্যক্তিগত এনক্রিপশন (Privacy Guarantee)
                                        </div>
                                        <div class="smaller text-secondary leading-relaxed">
                                            আপনার এআই এপিআই কীগুলো সার্ভারে <strong>AES-256 বিট অ্যালগরিদম</strong> দিয়ে এনক্রিপ্ট করে শুধুমাত্র আপনার ব্যক্তিগত অ্যাকাউন্টে সংরক্ষিত থাকে। অন্য কোনো ইউজার বা অ্যাডমিন আপনার কী দেখতে বা ব্যবহার করতে পারবে না। আপনার জেনারেট করা প্রতিটি বাংলা আর্টিকেল শুধুমাত্র আপনার নিজস্ব কী দিয়েই প্রসেস হয়।
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form @submit.prevent="handleSaveAiSettings" novalidate>
                                <!-- Primary Provider Selection -->
                                <div class="mb-4 p-3 bg-light rounded-3 border">
                                    <label class="form-label small fw-bold text-dark mb-2">
                                        <i class="bi bi-sliders text-primary me-1"></i>আপনার পছন্দসই প্রাইমারি এআই প্রোভাইডার নির্বাচন করুন:
                                    </label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input
                                                id="providerGroq"
                                                v-model="primaryProvider"
                                                class="form-check-input"
                                                type="radio"
                                                value="groq"
                                            />
                                            <label class="form-check-label small fw-semibold text-dark" for="providerGroq">
                                                <strong>Groq Cloud</strong> (দ্রুততম ~৫ সেকেন্ড, প্রস্তাবিত)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input
                                                id="providerGemini"
                                                v-model="primaryProvider"
                                                class="form-check-input"
                                                type="radio"
                                                value="gemini"
                                            />
                                            <label class="form-check-label small fw-semibold text-dark" for="providerGemini">
                                                <strong>Google Gemini</strong> (গুগল অফিসিয়াল মডেল)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-text smaller text-muted mt-2">
                                        একটি প্রোভাইডারের কোটা শেষ হলে সিস্টেম স্বয়ংক্রিয়ভাবে অপরটিতে ফলব্যাক করবে।
                                    </div>
                                </div>

                                <!-- Groq API Setup Card -->
                                <div class="card border rounded-3 p-3 mb-4 bg-white">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-dark text-white rounded p-1 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-lightning-fill text-warning"></i>
                                            </span>
                                            <span class="fw-bold text-dark">Groq API Key</span>
                                            <span v-if="primaryProvider === 'groq'" class="badge bg-primary-subtle text-primary smaller">
                                                প্রাইমারি
                                            </span>
                                        </div>

                                        <!-- Status Badge -->
                                        <div>
                                            <span
                                                v-if="aiSettings?.groq_configured && !clearGroqKey"
                                                class="badge bg-success-subtle text-success border border-success-subtle smaller"
                                            >
                                                <i class="bi bi-check-circle-fill me-1"></i>কী সেট করা আছে ({{ aiSettings.groq_masked_key }})
                                            </span>
                                            <span
                                                v-else
                                                class="badge bg-secondary-subtle text-secondary smaller"
                                            >
                                                কী দেওয়া হয়নি
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Key Input -->
                                    <div class="mb-3">
                                        <label for="groqKey" class="form-label smaller fw-semibold text-secondary mb-1">
                                            {{ aiSettings?.groq_configured && !clearGroqKey ? 'নতুন কী দিয়ে পরিবর্তন করতে চাইলে নিচে লিখুন:' : 'Groq API Key দিন:' }}
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-key text-muted"></i>
                                            </span>
                                            <input
                                                id="groqKey"
                                                v-model="groqApiKey"
                                                :type="showGroqKey ? 'text' : 'password'"
                                                class="form-control border-start-0 border-end-0"
                                                :placeholder="aiSettings?.groq_configured ? 'নতুন কী লিখুন...' : 'gsk_...'"
                                                :disabled="clearGroqKey"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                @click="showGroqKey = !showGroqKey"
                                            >
                                                <i class="bi" :class="showGroqKey ? 'bi-eye-slash' : 'bi-eye'"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Model Selection -->
                                    <div class="row g-2 align-items-center mb-3">
                                        <div class="col-sm-6">
                                            <label class="form-label smaller fw-semibold text-secondary mb-1">মডেল নির্বাচন:</label>
                                            <select v-model="groqModel" class="form-select form-select-sm">
                                                <option value="openai/gpt-oss-120b">openai/gpt-oss-120b (সেরা বাংলা কোয়ালিটি)</option>
                                                <option value="llama-3.3-70b-versatile">llama-3.3-70b-versatile</option>
                                                <option value="mixtral-8x7b-32768">mixtral-8x7b-32768</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6 text-sm-end pt-sm-4">
                                            <div class="d-flex align-items-center justify-content-sm-end gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-sm smaller"
                                                    :disabled="isTesting"
                                                    @click="handleTestKey('groq')"
                                                >
                                                    <span v-if="testingProvider === 'groq'" class="spinner-border spinner-border-sm me-1"></span>
                                                    <i v-else class="bi bi-lightning-charge me-1"></i>
                                                    কানেকশন টেস্ট করুন
                                                </button>
                                                <button
                                                    v-if="aiSettings?.groq_configured"
                                                    type="button"
                                                    class="btn btn-outline-danger btn-sm smaller"
                                                    @click="clearGroqKey = !clearGroqKey; groqApiKey = ''"
                                                >
                                                    {{ clearGroqKey ? 'মুছে ফেলা বাতিল' : 'কী মুছুন' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Feedback msg -->
                                    <div v-if="testFeedback['groq']" class="smaller p-2 rounded mb-2" :class="testFeedback['groq'].ok ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                                        <i class="bi" :class="testFeedback['groq'].ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
                                        {{ testFeedback['groq'].message }}
                                    </div>

                                    <div class="smaller text-muted">
                                        💡 Groq থেকে বিনামূল্যে এক ক্লিকে API Key পেতে
                                        <a href="https://console.groq.com/keys" target="_blank" class="fw-semibold text-decoration-none">
                                            console.groq.com/keys
                                        </a>-এ যান।
                                    </div>
                                </div>

                                <!-- Google Gemini API Setup Card -->
                                <div class="card border rounded-3 p-3 mb-4 bg-white">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary text-white rounded p-1 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                                <i class="bi bi-stars"></i>
                                            </span>
                                            <span class="fw-bold text-dark">Google Gemini API Key</span>
                                            <span v-if="primaryProvider === 'gemini'" class="badge bg-primary-subtle text-primary smaller">
                                                প্রাইমারি
                                            </span>
                                        </div>

                                        <!-- Status Badge -->
                                        <div>
                                            <span
                                                v-if="aiSettings?.gemini_configured && !clearGeminiKey"
                                                class="badge bg-success-subtle text-success border border-success-subtle smaller"
                                            >
                                                <i class="bi bi-check-circle-fill me-1"></i>কী সেট করা আছে ({{ aiSettings.gemini_masked_key }})
                                            </span>
                                            <span
                                                v-else
                                                class="badge bg-secondary-subtle text-secondary smaller"
                                            >
                                                কী দেওয়া হয়নি
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Key Input -->
                                    <div class="mb-3">
                                        <label for="geminiKey" class="form-label smaller fw-semibold text-secondary mb-1">
                                            {{ aiSettings?.gemini_configured && !clearGeminiKey ? 'নতুন কী দিয়ে পরিবর্তন করতে চাইলে নিচে লিখুন:' : 'Gemini API Key দিন:' }}
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="bi bi-key text-muted"></i>
                                            </span>
                                            <input
                                                id="geminiKey"
                                                v-model="geminiApiKey"
                                                :type="showGeminiKey ? 'text' : 'password'"
                                                class="form-control border-start-0 border-end-0"
                                                :placeholder="aiSettings?.gemini_configured ? 'নতুন কী লিখুন...' : 'AIzaSy...'"
                                                :disabled="clearGeminiKey"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                @click="showGeminiKey = !showGeminiKey"
                                            >
                                                <i class="bi" :class="showGeminiKey ? 'bi-eye-slash' : 'bi-eye'"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Model Selection -->
                                    <div class="row g-2 align-items-center mb-3">
                                        <div class="col-sm-6">
                                            <label class="form-label smaller fw-semibold text-secondary mb-1">মডেল নির্বাচন:</label>
                                            <select v-model="geminiModel" class="form-select form-select-sm">
                                                <option value="gemini-3-flash-preview">gemini-3-flash-preview (সুপার ফাস্ট ও ফ্রি)</option>
                                                <option value="gemini-2.5-pro">gemini-2.5-pro (অ্যাডভান্সড)</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6 text-sm-end pt-sm-4">
                                            <div class="d-flex align-items-center justify-content-sm-end gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-sm smaller"
                                                    :disabled="isTesting"
                                                    @click="handleTestKey('gemini')"
                                                >
                                                    <span v-if="testingProvider === 'gemini'" class="spinner-border spinner-border-sm me-1"></span>
                                                    <i v-else class="bi bi-stars me-1"></i>
                                                    কানেকশন টেস্ট করুন
                                                </button>
                                                <button
                                                    v-if="aiSettings?.gemini_configured"
                                                    type="button"
                                                    class="btn btn-outline-danger btn-sm smaller"
                                                    @click="clearGeminiKey = !clearGeminiKey; geminiApiKey = ''"
                                                >
                                                    {{ clearGeminiKey ? 'মুছে ফেলা বাতিল' : 'কী মুছুন' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Feedback msg -->
                                    <div v-if="testFeedback['gemini']" class="smaller p-2 rounded mb-2" :class="testFeedback['gemini'].ok ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                                        <i class="bi" :class="testFeedback['gemini'].ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'"></i>
                                        {{ testFeedback['gemini'].message }}
                                    </div>

                                    <div class="smaller text-muted">
                                        💡 Google AI Studio থেকে বিনামূল্যে API Key পেতে
                                        <a href="https://aistudio.google.com/app/apikey" target="_blank" class="fw-semibold text-decoration-none">
                                            aistudio.google.com
                                        </a>-এ যান।
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="text-end pt-2 border-top">
                                    <button
                                        type="submit"
                                        class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-xs"
                                        :disabled="isSaving"
                                    >
                                        <span v-if="isSaving" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                        <i v-else class="bi bi-shield-check me-1"></i>
                                        এআই সেটিংস সংরক্ষণ করুন
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.78rem;
}

.avatar-box {
    width: 52px;
    height: 52px;
}

.shadow-xs {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.transition-all {
    transition: all 0.2s ease-in-out;
}
</style>

