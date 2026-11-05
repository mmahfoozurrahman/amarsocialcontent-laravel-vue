<script setup>
import { onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { usePreloader } from '@/composables/usePreloader';
import { useNotification } from '@/composables/useNotification';
import AppNavbar from '@/layouts/AppNavbar.vue';
import UserTourGuideModal from '@/components/tour/UserTourGuideModal.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';

const { fetchCurrentUser } = useAuth();
const { isLoading } = usePreloader();
const { notification, clear } = useNotification();

onMounted(async () => {
    await fetchCurrentUser();
});
</script>

<template>
    <div class="app-root d-flex flex-column min-vh-100">
        <!-- Top Route Preloader Progress Bar -->
        <div v-if="isLoading" id="app-preloader"></div>

        <!-- Global Floating Toast Notification Alert -->
        <div v-if="notification" class="toast-stack position-fixed top-0 end-0">
            <div
                class="toast app-toast show overflow-hidden"
                role="alert"
                aria-live="assertive"
                aria-atomic="true"
            >
                <div
                    class="d-flex align-items-center p-3 gap-2"
                    :class="{
                        'toast-success': notification.type === 'success',
                        'toast-danger': notification.type === 'danger',
                        'toast-info': notification.type === 'info' || !notification.type,
                    }"
                >
                    <i v-if="notification.type === 'success'" class="bi bi-check-circle-fill fs-5"></i>
                    <i v-else-if="notification.type === 'danger'" class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <i v-else class="bi bi-info-circle-fill fs-5"></i>

                    <div class="toast-body p-0 flex-grow-1 fw-semibold small">
                        {{ notification.message }}
                    </div>

                    <button
                        type="button"
                        class="btn-close ms-auto"
                        :class="{ 'btn-close-white': notification.type !== 'danger' }"
                        aria-label="Close"
                        @click="clear"
                    ></button>
                </div>
            </div>
        </div>

        <!-- Main Navigation Bar -->
        <AppNavbar />

        <!-- User Tour Guide Modal (Available Globally) -->
        <UserTourGuideModal />

        <!-- Global Confirmation Dialog (replaces native window.confirm) -->
        <ConfirmDialog />

        <!-- Router View Shell -->
        <main class="flex-grow-1">
            <router-view />
        </main>

        <footer class="app-footer mt-auto">
            <div class="container">
                <div class="footer-band d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-bold footer-brand">Amar<span>SocialContent</span></div>
                        <div class="smaller footer-copy footer-tagline">সোশ্যাল মিডিয়া জ্ঞানভাণ্ডার ও পার্সোনাল সেকেন্ড ব্রেন</div>
                    </div>
                    <div class="smaller footer-copy">
                        &copy; {{ new Date().getFullYear() }} সর্বস্বত্ব সংরক্ষিত।
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.app-footer {
    padding: 1.5rem 0 2.25rem;
}

.footer-band {
    background: var(--brand-soft);
    border-radius: var(--radius-card);
    padding: 1.35rem 1.6rem;
}

.footer-tagline {
    max-width: 44ch;
}

.footer-brand {
    color: var(--text-primary);
    letter-spacing: -0.02em;
}

.footer-brand span {
    color: var(--brand-primary);
}

.footer-copy {
    color: var(--text-secondary);
}

.smaller {
    font-size: 0.8rem;
}

.toast-stack {
    z-index: 100000;
    padding: 4.75rem 1rem 1rem;
    pointer-events: none;
}

.app-toast {
    pointer-events: auto;
    max-width: min(360px, calc(100vw - 1.5rem));
    border-radius: var(--radius-control);
    border: 1px solid var(--border-default);
    box-shadow: var(--card-shadow-hover);
    animation: fadeIn 0.2s ease;
}

.toast-success,
.toast-info {
    background: var(--brand-primary);
    color: var(--text-on-brand);
}

.toast-danger {
    background: #fef2f2;
    color: #b91c1c;
    border-color: #fecaca;
}

@media (max-width: 575.98px) {
    .toast-stack {
        padding: 4.5rem 1rem 1rem;
    }

    .footer-band {
        text-align: center;
        justify-content: center !important;
        padding: 1.25rem 1rem;
    }
}
</style>
