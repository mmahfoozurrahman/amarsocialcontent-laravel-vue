<script setup>
import { computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import { usePreloader } from '@/composables/usePreloader';
import { useNotification } from '@/composables/useNotification';
import { useTour } from '@/composables/useTour';
import { useNavigation } from '@/composables/useNavigation';
import AppNavbar from '@/layouts/AppNavbar.vue';
import UserTourGuideModal from '@/components/tour/UserTourGuideModal.vue';
import ConfirmDialog from '@/components/common/ConfirmDialog.vue';

const { fetchCurrentUser, isAuthenticated, isAdmin } = useAuth();
const { isLoading } = usePreloader();
const { notification, clear } = useNotification();
const { openTour } = useTour();
const { mainNavLinks, guestNavLinks } = useNavigation();

const footerNavLinks = computed(() => {
    if (!isAuthenticated.value) {
        return guestNavLinks;
    }

    return mainNavLinks.filter((link) => !link.adminOnly || isAdmin.value);
});

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
                <div class="row g-4 g-lg-5">
                    <!-- Brand -->
                    <div class="col-lg-5">
                        <router-link to="/" class="footer-brand d-inline-flex align-items-center gap-2">
                            <span class="footer-brand-icon">
                                <i class="bi bi-brain"></i>
                            </span>
                            <span class="footer-brand-text">আমার<span>সোশ্যাল</span></span>
                        </router-link>

                        <p class="footer-brand-tagline">আমার সোশ্যাল মিডিয়ার জ্ঞানভাণ্ডার ও পার্সোনাল সেকেন্ড ব্রেন</p>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="footer-chip"><i class="bi bi-shield-lock"></i> নিজস্ব এআই কী (BYOK)</span>
                            <span class="footer-chip"><i class="bi bi-stars"></i> খাঁটি বাংলা সারসংক্ষেপ</span>
                            <span class="footer-chip"><i class="bi bi-search-heart"></i> সিম্যান্টিক সার্চ</span>
                        </div>
                    </div>

                    <!-- Platform links (same source as the navbar) -->
                    <div class="col-6 col-lg-4">
                        <h2 class="footer-heading">প্ল্যাটফর্ম</h2>
                        <ul class="footer-links list-unstyled mb-0">
                            <li v-for="link in footerNavLinks" :key="link.label">
                                <router-link :to="link.to" class="footer-link">
                                    <i class="bi" :class="link.icon"></i>
                                    <span>{{ link.label }}</span>
                                </router-link>
                            </li>
                        </ul>
                    </div>

                    <!-- Account -->
                    <div class="col-6 col-lg-3">
                        <h2 class="footer-heading">অ্যাকাউন্ট</h2>
                        <ul class="footer-links list-unstyled mb-0">
                            <template v-if="isAuthenticated">
                                <li>
                                    <router-link to="/profile" class="footer-link">
                                        <i class="bi bi-gear"></i>
                                        <span>প্রোফাইল ও এআই কী</span>
                                    </router-link>
                                </li>
                                <li>
                                    <button type="button" class="footer-link" @click="openTour(0)">
                                        <i class="bi bi-compass"></i>
                                        <span>প্ল্যাটফর্ম ট্যুর গাইড</span>
                                    </button>
                                </li>
                            </template>
                            <template v-else>
                                <li>
                                    <router-link to="/login" class="footer-link">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                        <span>পাসওয়ার্ডলেস লগইন</span>
                                    </router-link>
                                </li>
                                <li>
                                    <router-link :to="{ path: '/', hash: '#get-started' }" class="footer-link">
                                        <i class="bi bi-rocket-takeoff"></i>
                                        <span>শুরু করুন</span>
                                    </router-link>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                <div class="footer-meta d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="smaller footer-copy">
                        &copy; {{ new Date().getFullYear() }} AmarSocialContent — সর্বস্বত্ব সংরক্ষিত।
                    </span>
                    <span class="smaller footer-copy">
                        নিজস্ব এআই কী দিয়ে চলে · আপনার ডেটা আপনার নিয়ন্ত্রণে
                    </span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.app-footer {
    margin-top: auto;
    background: var(--surface-sunken);
    border-top: 1px solid var(--border-default);
    padding: 3rem 0 1.75rem;
}

.footer-brand {
    text-decoration: none;
}

.footer-brand-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--brand-primary);
    color: var(--text-on-brand);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 14px -10px rgba(var(--brand-primary-rgb), 0.9);
}

.footer-brand-text {
    font-size: 1.12rem;
    font-weight: 700;
    letter-spacing: -0.03em;
    color: var(--text-primary);
}

.footer-brand-text span {
    color: var(--brand-primary);
}

.footer-brand-tagline {
    max-width: 46ch;
    margin-top: 0.6rem;
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.6;
    letter-spacing: 0.01em;
    color: var(--brand-primary);
}

.footer-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--brand-primary);
    background: var(--surface-card);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-pill);
    padding: 0.3rem 0.75rem;
}

.footer-heading {
    margin-bottom: 0.9rem;
    font-size: 0.76rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--text-secondary);
}

.footer-links {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}

.footer-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0;
    border: 0;
    background: none;
    font-family: inherit;
    font-size: 0.9rem;
    text-align: start;
    text-decoration: none;
    color: var(--text-secondary);
    cursor: pointer;
    transition: color 0.15s ease, transform 0.15s ease;
}

.footer-link i {
    font-size: 0.95rem;
    opacity: 0.85;
}

.footer-link:hover {
    color: var(--brand-primary);
    transform: translateX(2px);
}

.footer-meta {
    margin-top: 2.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--border-default);
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

@media (max-width: 991.98px) {
    .app-footer {
        padding: 2.25rem 0 1.5rem;
    }
}

@media (max-width: 575.98px) {
    .toast-stack {
        padding: 4.5rem 1rem 1rem;
    }

    .footer-meta {
        justify-content: center !important;
        text-align: center;
    }
}
</style>
