<script setup>
import { onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { usePreloader } from '@/composables/usePreloader';
import { useNotification } from '@/composables/useNotification';
import AppNavbar from '@/layouts/AppNavbar.vue';
import UserTourGuideModal from '@/components/tour/UserTourGuideModal.vue';

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
        <div v-if="notification" class="position-fixed top-0 end-0 p-3 pt-4" style="z-index: 100000;">
            <div
                class="toast show shadow-lg border-0 rounded-4 overflow-hidden"
                role="alert"
                aria-live="assertive"
                aria-atomic="true"
            >
                <div
                    class="d-flex align-items-center p-3 gap-2"
                    :class="{
                        'bg-success text-white': notification.type === 'success',
                        'bg-danger text-white': notification.type === 'danger',
                        'bg-primary text-white': notification.type === 'info' || !notification.type,
                    }"
                >
                    <i v-if="notification.type === 'success'" class="bi bi-check-circle-fill fs-5"></i>
                    <i v-else-if="notification.type === 'danger'" class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <i v-else class="bi bi-info-circle-fill fs-5"></i>

                    <div class="toast-body p-0 flex-grow-1 fw-semibold small text-white" style="color: #ffffff !important;">
                        {{ notification.message }}
                    </div>

                    <button
                        type="button"
                        class="btn-close btn-close-white ms-auto"
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

        <!-- Router View Shell -->
        <main class="flex-grow-1">
            <router-view />
        </main>

        <!-- Modern Footer -->
        <footer class="bg-white border-top py-4 mt-auto">
            <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2 text-muted smaller">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark">Amar<span class="text-primary">SocialContent</span></span>
                    <span>&mdash; সোশ্যাল মিডিয়া জ্ঞানভাণ্ডার ও পার্সোনাল সেকেন্ড ব্রেন</span>
                </div>
                <div>
                    <span>&copy; {{ new Date().getFullYear() }} সর্বস্বত্ব সংরক্ষিত।</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.8rem;
}
</style>
