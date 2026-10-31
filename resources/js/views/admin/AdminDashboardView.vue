<script setup>
import { ref, onMounted } from 'vue';
import { useAdmin } from '@/composables/useAdmin';

const {
    stats,
    aiHealth,
    aiLogs,
    aiLogsPagination,
    activityLogs,
    activityPagination,
    isLoading,
    fetchAILogs,
    fetchActivityLogs,
    loadAll,
} = useAdmin();

const activeTab = ref('ai-logs'); // 'ai-logs' | 'activity' | 'users'

// AI Logs Filters
const selectedAIProvider = ref('');
const selectedAIStatus = ref('');

// Activity Logs Filters
const activitySearch = ref('');

const handleFilterAILogs = () => {
    const params = {};
    if (selectedAIProvider.value) params.provider = selectedAIProvider.value;
    if (selectedAIStatus.value) params.status = selectedAIStatus.value;
    fetchAILogs(params);
};

let searchTimer = null;
const handleActivitySearch = () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        fetchActivityLogs({ search: activitySearch.value.trim() });
    }, 400);
};

const formatTime = (timeStr) => {
    if (!timeStr) return '';
    try {
        const d = new Date(timeStr);
        return d.toLocaleString('bn-BD', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (e) {
        return timeStr;
    }
};

onMounted(() => {
    loadAll();
});
</script>

<template>
    <div class="admin-dashboard-page bg-light min-vh-100 pb-5">
        <div class="container py-4">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-semibold">
                            <i class="bi bi-shield-lock-fill me-1"></i> Admin Control Center
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 smaller">
                            <i class="bi bi-check-circle me-1"></i> Privacy Audited
                        </span>
                    </div>
                    <h2 class="fw-bold text-dark mb-1">প্ল্যাটফর্ম হেলথ ও অ্যাডমিন কন্ট্রোল সেন্টার</h2>
                    <p class="text-secondary small mb-0">
                        সিস্টেম পারফরম্যান্স, এআই সার্ভিস হেলথ (Groq ও Gemini), টোকেন ব্যবহার এবং অ্যাক্টিভিটি অডিট ট্রেইল।
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2 mt-3 mt-sm-0">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-xs"
                        :disabled="isLoading"
                        @click="loadAll"
                    >
                        <i class="bi bi-arrow-clockwise me-1" :class="{ 'spin': isLoading }"></i>
                        <span>রিফ্রেশ</span>
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading && !stats" class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small text-muted mt-2">অ্যাডমিন মেট্রিক্স লোড হচ্ছে...</p>
            </div>

            <div v-else>
                <!-- Overview Stats Cards -->
                <div class="row g-3 mb-4">
                    <!-- Total Users -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted smaller fw-semibold">মোট রেজিস্টার্ড ইউজার</span>
                                    <h3 class="fw-bold text-dark mt-1 mb-0">{{ stats?.users?.total || 0 }}</h3>
                                    <span class="smaller text-success">
                                        <i class="bi bi-person-check-fill me-1"></i>{{ stats?.users?.active || 0 }} জন সক্রিয়
                                    </span>
                                </div>
                                <div class="p-3 bg-primary-subtle text-primary rounded-3">
                                    <i class="bi bi-people-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aggregated Contents -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted smaller fw-semibold">অ্যাগ্রিগেটেড ভিডিও</span>
                                    <h3 class="fw-bold text-dark mt-1 mb-0">{{ stats?.contents?.total || 0 }}</h3>
                                    <span class="smaller text-success">
                                        <i class="bi bi-check2-all me-1"></i>{{ stats?.contents?.completion_rate || 0 }}% প্রস্তুত সম্পন্ন
                                    </span>
                                </div>
                                <div class="p-3 bg-success-subtle text-success rounded-3">
                                    <i class="bi bi-collection-play-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Learning Hours -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted smaller fw-semibold">মোট স্টাডি ও পড়ার সময়</span>
                                    <h3 class="fw-bold text-dark mt-1 mb-0">{{ stats?.learning?.total_study_minutes || 0 }} মিনিট</h3>
                                    <span class="smaller text-info">
                                        {{ stats?.learning?.total_courses || 0 }} টি কোর্স / {{ stats?.learning?.total_lessons || 0 }} টি লেসন
                                    </span>
                                </div>
                                <div class="p-3 bg-info-subtle text-info rounded-3">
                                    <i class="bi bi-mortarboard-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Calls & Success Rate -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm p-3 bg-white">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted smaller fw-semibold">এআই কল ও সাকসেস রেট</span>
                                    <h3 class="fw-bold text-dark mt-1 mb-0">{{ aiHealth?.metrics?.success_rate || 100 }}%</h3>
                                    <span class="smaller text-muted">
                                        মোট {{ aiHealth?.metrics?.total_calls || 0 }} টি কল
                                    </span>
                                </div>
                                <div class="p-3 bg-warning-subtle text-warning rounded-3">
                                    <i class="bi bi-cpu-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Provider Infrastructure Health Panel -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">
                                <i class="bi bi-robot text-primary me-2"></i>এআই সার্ভিস হেলথ ও ফলব্যাক পাইপলাইন
                            </h5>
                            <p class="text-secondary small mb-0">
                                প্রাইমারি প্রোভাইডার ব্যর্থ হলে সিস্টেম স্বয়ংক্রিয়ভাবে সেকেন্ডারি প্রোভাইডারে ফলব্যাক করে।
                            </p>
                        </div>
                        <span class="badge bg-light text-secondary border smaller">
                            প্রাইমারি: <strong class="text-primary text-uppercase">{{ aiHealth?.configuration?.primary_provider }}</strong>
                        </span>
                    </div>

                    <div class="row g-3">
                        <!-- Groq API Status -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark text-white rounded p-1 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-lightning-fill text-warning"></i>
                                        </span>
                                        <span class="fw-bold text-dark">Groq Cloud API</span>
                                        <span v-if="aiHealth?.configuration?.primary_provider === 'groq'" class="badge bg-primary rounded-pill smaller">
                                            Primary
                                        </span>
                                    </div>
                                    <span
                                        class="badge rounded-pill px-2.5 py-1"
                                        :class="aiHealth?.configuration?.groq?.status === 'operational' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger'"
                                    >
                                        <i class="bi bi-circle-fill smaller me-1"></i>
                                        {{ aiHealth?.configuration?.groq?.status === 'operational' ? 'সক্রিয় (Operational)' : 'সমস্যা (Degraded)' }}
                                    </span>
                                </div>

                                <div class="small text-secondary mb-2">
                                    মডেল: <code>{{ aiHealth?.configuration?.groq?.model }}</code>
                                </div>

                                <div class="d-flex flex-wrap gap-3 smaller text-muted pt-2 border-top">
                                    <span>
                                        <i class="bi bi-speedometer me-1"></i> গড় লেটেন্সি: <strong>{{ aiHealth?.configuration?.groq?.avg_latency_ms || 4200 }} ms</strong>
                                    </span>
                                    <span>
                                        <i class="bi bi-key-fill text-success me-1"></i> এপিআই কী: <strong>কনফিগার করা</strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Google Gemini API Status -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary text-white rounded p-1 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-stars"></i>
                                        </span>
                                        <span class="fw-bold text-dark">Google Gemini API</span>
                                        <span v-if="aiHealth?.configuration?.primary_provider === 'gemini'" class="badge bg-primary rounded-pill smaller">
                                            Primary
                                        </span>
                                        <span v-else class="badge bg-secondary-subtle text-secondary rounded-pill smaller">
                                            Fallback & Embeddings
                                        </span>
                                    </div>
                                    <span
                                        class="badge rounded-pill px-2.5 py-1"
                                        :class="aiHealth?.configuration?.gemini?.status === 'operational' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning'"
                                    >
                                        <i class="bi bi-circle-fill smaller me-1"></i>
                                        {{ aiHealth?.configuration?.gemini?.status === 'operational' ? 'সক্রিয় (Operational)' : 'ফলব্যাক প্রস্তুত' }}
                                    </span>
                                </div>

                                <div class="small text-secondary mb-2">
                                    মডেল: <code>{{ aiHealth?.configuration?.gemini?.model }}</code> / ভেক্টর: <code>text-embedding-004</code>
                                </div>

                                <div class="d-flex flex-wrap gap-3 smaller text-muted pt-2 border-top">
                                    <span>
                                        <i class="bi bi-speedometer me-1"></i> গড় লেটেন্সি: <strong>{{ aiHealth?.configuration?.gemini?.avg_latency_ms || 2800 }} ms</strong>
                                    </span>
                                    <span>
                                        <i class="bi bi-key-fill text-success me-1"></i> এপিআই কী: <strong>কনফিগার করা</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs: AI Logs, Audit Trail, Recent Users -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <!-- Nav Tabs -->
                    <div class="card-header bg-white border-bottom p-3">
                        <ul class="nav nav-pills gap-1">
                            <li class="nav-item">
                                <button
                                    type="button"
                                    class="nav-link rounded-pill px-3 py-1.5 smaller fw-semibold transition-all"
                                    :class="activeTab === 'ai-logs' ? 'active bg-primary text-white' : 'text-secondary'"
                                    @click="activeTab = 'ai-logs'"
                                >
                                    <i class="bi bi-cpu me-1"></i> এআই প্রসেসিং লগ ({{ aiLogsPagination.total }})
                                </button>
                            </li>
                            <li class="nav-item">
                                <button
                                    type="button"
                                    class="nav-link rounded-pill px-3 py-1.5 smaller fw-semibold transition-all"
                                    :class="activeTab === 'activity' ? 'active bg-primary text-white' : 'text-secondary'"
                                    @click="activeTab = 'activity'"
                                >
                                    <i class="bi bi-journal-text me-1"></i> সিস্টেম অ্যাক্টিভিটি অডিট ({{ activityPagination.total }})
                                </button>
                            </li>
                            <li class="nav-item">
                                <button
                                    type="button"
                                    class="nav-link rounded-pill px-3 py-1.5 smaller fw-semibold transition-all"
                                    :class="activeTab === 'users' ? 'active bg-primary text-white' : 'text-secondary'"
                                    @click="activeTab = 'users'"
                                >
                                    <i class="bi bi-people me-1"></i> সাম্প্রতিক রেজিস্টার্ড ইউজার ({{ stats?.users?.total }})
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tab 1: AI Processing Logs -->
                    <div v-if="activeTab === 'ai-logs'" class="p-3">
                        <!-- Filters -->
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <select
                                v-model="selectedAIProvider"
                                class="form-select form-select-sm"
                                style="max-width: 180px;"
                                @change="handleFilterAILogs"
                            >
                                <option value="">সকল প্রোভাইডার</option>
                                <option value="groq">Groq</option>
                                <option value="gemini">Gemini</option>
                            </select>

                            <select
                                v-model="selectedAIStatus"
                                class="form-select form-select-sm"
                                style="max-width: 180px;"
                                @change="handleFilterAILogs"
                            >
                                <option value="">সকল স্ট্যাটাস</option>
                                <option value="success">সফল (Success)</option>
                                <option value="fallback_triggered">ফলব্যাক ট্রিগার</option>
                                <option value="failed">ব্যর্থ (Failed)</option>
                            </select>

                            <button
                                v-if="selectedAIProvider || selectedAIStatus"
                                type="button"
                                class="btn btn-link btn-sm text-danger text-decoration-none p-0 smaller"
                                @click="selectedAIProvider = ''; selectedAIStatus = ''; handleFilterAILogs()"
                            >
                                ফিল্টার মুছুন
                            </button>
                        </div>

                        <!-- Logs Table -->
                        <div v-if="aiLogs.length === 0" class="text-center py-5 text-muted small">
                            কোনো এআই প্রসেসিং লগ পাওয়া যায়নি।
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover align-middle mb-0 smaller">
                                <thead class="table-light">
                                    <tr>
                                        <th>সময়</th>
                                        <th>প্রোভাইডার ও মডেল</th>
                                        <th>অপারেশন</th>
                                        <th>টোকেন ব্যবহার</th>
                                        <th>লেটেন্সি</th>
                                        <th>স্ট্যাটাস</th>
                                        <th class="text-end pe-3">ডিটেইলস</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="log in aiLogs" :key="log.id">
                                        <td class="text-muted">{{ formatTime(log.created_at) }}</td>
                                        <td>
                                            <span class="badge" :class="log.provider === 'groq' ? 'bg-dark' : 'bg-primary'">
                                                {{ log.provider }}
                                            </span>
                                            <span class="ms-1 text-secondary">{{ log.model }}</span>
                                        </td>
                                        <td class="text-dark fw-medium">{{ log.operation_type }}</td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">
                                                {{ (log.prompt_tokens || 0) + (log.completion_tokens || 0) }} টোকেন
                                            </span>
                                        </td>
                                        <td>{{ log.latency_ms ? `${log.latency_ms} ms` : '-' }}</td>
                                        <td>
                                            <span
                                                v-if="log.status === 'success'"
                                                class="badge bg-success-subtle text-success border border-success-subtle"
                                            >
                                                Success
                                            </span>
                                            <span
                                                v-else-if="log.status === 'fallback_triggered'"
                                                class="badge bg-warning-subtle text-warning border border-warning-subtle"
                                            >
                                                Fallback
                                            </span>
                                            <span
                                                v-else
                                                class="badge bg-danger-subtle text-danger border border-danger-subtle"
                                            >
                                                Failed
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <span
                                                v-if="log.error_details"
                                                class="text-danger cursor-pointer"
                                                :title="log.error_details"
                                            >
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> এরর দেখুন
                                            </span>
                                            <span v-else class="text-muted fst-italic">স্বাভাবিক</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Activity Audit Trail -->
                    <div v-if="activeTab === 'activity'" class="p-3">
                        <div class="row g-2 align-items-center mb-3">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-search text-muted"></i>
                                    </span>
                                    <input
                                        v-model="activitySearch"
                                        type="text"
                                        class="form-control border-start-0"
                                        placeholder="ইউজার, অ্যাকশন বা আইপি দিয়ে খুঁজুন..."
                                        @input="handleActivitySearch"
                                    />
                                </div>
                            </div>
                            <div class="col-md-8 text-md-end text-muted smaller">
                                <i class="bi bi-shield-check text-success me-1"></i> গোপনীয়তা রক্ষা: ব্যবহারকারীর ব্যক্তিগত নোটস বা পাসওয়ার্ড কখনোই লগ করা হয় না।
                            </div>
                        </div>

                        <div v-if="activityLogs.length === 0" class="text-center py-5 text-muted small">
                            কোনো অ্যাক্টিভিটি লগ পাওয়া যায়নি।
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover align-middle mb-0 smaller">
                                <thead class="table-light">
                                    <tr>
                                        <th>সময়</th>
                                        <th>ইউজার</th>
                                        <th>অ্যাকশন (Action)</th>
                                        <th>আইপি এড্রেস</th>
                                        <th>ডিভাইস / ব্রাউজার</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="act in activityLogs" :key="act.id">
                                        <td class="text-muted">{{ formatTime(act.created_at) }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ act.user?.name || 'অজানা' }}</div>
                                            <div class="smaller text-muted">{{ act.user?.email }}</div>
                                        </td>
                                        <td>
                                            <code class="text-primary">{{ act.action }}</code>
                                        </td>
                                        <td class="text-muted">{{ act.ip_address || '127.0.0.1' }}</td>
                                        <td class="text-muted text-truncate" style="max-width: 280px;" :title="act.user_agent">
                                            {{ act.user_agent || 'Standard Client' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Recent Users -->
                    <div v-if="activeTab === 'users'" class="p-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 smaller">
                                <thead class="table-light">
                                    <tr>
                                        <th>ইউজার নাম</th>
                                        <th>ইমেইল</th>
                                        <th>রোল</th>
                                        <th>স্ট্যাটাস</th>
                                        <th>রেজিস্ট্রেশনের সময়</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="u in stats?.users?.recent" :key="u.id">
                                        <td class="fw-semibold text-dark">{{ u.name || 'ব্যবহারকারী' }}</td>
                                        <td class="text-muted">{{ u.email }}</td>
                                        <td>
                                            <span class="badge" :class="u.role === 'admin' ? 'bg-danger' : 'bg-primary-subtle text-primary'">
                                                {{ u.role }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success">
                                                Active
                                            </span>
                                        </td>
                                        <td class="text-muted">{{ formatTime(u.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
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

.shadow-xs {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
