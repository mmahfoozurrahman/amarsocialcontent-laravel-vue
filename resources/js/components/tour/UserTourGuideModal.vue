<script setup>
import { computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useTour } from '@/composables/useTour';
import { useAuth } from '@/composables/useAuth';

const router = useRouter();
const { user } = useAuth();
const {
    isOpen,
    currentStep,
    steps,
    closeTour,
    nextStep,
    prevStep,
    goToStep,
    markTourAsSeen
} = useTour();

const current = computed(() => steps[currentStep.value] || steps[0]);
const isFirstStep = computed(() => currentStep.value === 0);
const isLastStep = computed(() => currentStep.value === steps.length - 1);
const progressPercent = computed(() => Math.round(((currentStep.value + 1) / steps.length) * 100));

const handleDismiss = () => {
    closeTour(user.value?.id);
};

const handleFinish = () => {
    markTourAsSeen(user.value?.id);
    closeTour(user.value?.id);
};

const handleNavigate = (route) => {
    if (!route) return;
    markTourAsSeen(user.value?.id);
    closeTour(user.value?.id);
    router.push(route);
};

// Handle keyboard ESC or Arrow keys
const handleKeydown = (e) => {
    if (!isOpen.value) return;
    if (e.key === 'Escape') {
        handleDismiss();
    } else if (e.key === 'ArrowRight') {
        nextStep();
    } else if (e.key === 'ArrowLeft') {
        prevStep();
    }
};

watch(isOpen, (newVal) => {
    if (newVal) {
        window.addEventListener('keydown', handleKeydown);
    } else {
        window.removeEventListener('keydown', handleKeydown);
    }
});
</script>

<template>
    <div v-if="isOpen" class="tour-backdrop d-flex align-items-center justify-content-center p-3">
        <div class="tour-card card border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Progress Line at the very top -->
            <div class="progress rounded-0" style="height: 4px;">
                <div
                    class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                    role="progressbar"
                    :style="{ width: `${progressPercent}%` }"
                    aria-label="ট্যুর প্রগ্রেস"
                ></div>
            </div>

            <!-- Header -->
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span :class="`badge bg-${current.badgeColor}-subtle text-${current.badgeColor} border border-${current.badgeColor}-subtle rounded-pill px-3 py-1.5 fw-semibold small`">
                        <i :class="`bi ${current.icon} me-1`"></i>
                        {{ current.badge }}
                    </span>
                    <span class="text-muted smaller fw-medium">
                        ধাপ {{ currentStep + 1 }} / {{ steps.length }}
                    </span>
                </div>
                <button
                    type="button"
                    class="btn-close shadow-none p-2"
                    aria-label="Close"
                    @click="handleDismiss"
                    title="ট্যুর বন্ধ করুন"
                ></button>
            </div>

            <!-- Body -->
            <div class="card-body px-4 py-3">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div
                        :class="`tour-icon-box ${current.iconBg} ${current.iconColor} rounded-4 d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs`"
                    >
                        <i :class="`bi ${current.icon} fs-2`"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark tracking-tight">
                            {{ current.title }}
                        </h4>
                        <p class="text-secondary small mb-0 lh-base">
                            {{ current.description }}
                        </p>
                    </div>
                </div>

                <!-- Tips / Features Box -->
                <div class="tour-tips-box bg-light rounded-3 p-3 border mb-3">
                    <div class="fw-semibold text-dark smaller text-uppercase tracking-wider mb-2 d-flex align-items-center gap-1.5">
                        <i class="bi bi-lightbulb-fill text-warning"></i>
                        <span>গুরুত্বপূর্ণ সুবিধা ও টিপস:</span>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li
                            v-for="(tip, idx) in current.tips"
                            :key="idx"
                            class="d-flex align-items-start gap-2 small text-dark"
                        >
                            <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                            <span class="lh-sm">{{ tip }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Feature Navigation Shortcut if available -->
                <div v-if="current.actionRoute && current.actionLabel" class="d-flex justify-content-end mb-2">
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1.5 fw-medium d-inline-flex align-items-center gap-1.5"
                        @click="handleNavigate(current.actionRoute)"
                    >
                        <span>{{ current.actionLabel }}</span>
                        <i class="bi bi-arrow-up-right-circle"></i>
                    </button>
                </div>
            </div>

            <!-- Footer with Stepper Controls -->
            <div class="card-footer bg-white border-top px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Step Dots Indicator -->
                <div class="d-flex align-items-center gap-1.5">
                    <button
                        v-for="(_, index) in steps"
                        :key="index"
                        type="button"
                        class="tour-dot btn p-0 border-0"
                        :class="{ 'active': currentStep === index }"
                        :title="`ধাপ ${index + 1}`"
                        @click="goToStep(index)"
                    ></button>
                </div>

                <!-- Buttons -->
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button
                        type="button"
                        class="btn btn-link btn-sm text-secondary text-decoration-none px-2"
                        @click="handleDismiss"
                    >
                        স্কিপ করুন
                    </button>

                    <button
                        type="button"
                        class="btn btn-light btn-sm rounded-pill px-3 py-1.5 border"
                        :disabled="isFirstStep"
                        @click="prevStep"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        পূর্ববর্তী
                    </button>

                    <button
                        v-if="!isLastStep"
                        type="button"
                        class="btn btn-primary btn-sm rounded-pill px-4 py-1.5 fw-medium shadow-xs"
                        @click="nextStep"
                    >
                        পরবর্তী
                        <i class="bi bi-arrow-right ms-1"></i>
                    </button>

                    <button
                        v-else
                        type="button"
                        class="btn btn-success btn-sm rounded-pill px-4 py-1.5 fw-medium shadow-xs"
                        @click="handleFinish"
                    >
                        <i class="bi bi-check2-circle me-1"></i>
                        ট্যুর সম্পন্ন করুন
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tour-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(28, 33, 30, 0.62);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 1060;
    animation: fadeIn 0.2s ease-out;
}

.tour-card {
    width: 100%;
    max-width: 580px;
    background: var(--surface-card);
    box-shadow: 0 24px 48px -20px rgba(28, 33, 30, 0.35);
    animation: slideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.tour-icon-box {
    width: 60px;
    height: 60px;
}

.tour-tips-box {
    background: var(--surface-sunken);
    border-color: var(--border-default);
}

.tour-dot {
    width: 9px;
    height: 9px;
    border-radius: 9999px;
    background-color: var(--border-hover);
    transition: all 0.2s ease;
}

.tour-dot:hover {
    background-color: var(--text-secondary);
}

.tour-dot.active {
    width: 24px;
    background-color: var(--brand-primary);
}

.smaller {
    font-size: 0.78rem;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>

