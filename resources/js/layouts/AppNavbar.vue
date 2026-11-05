<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useNotification } from '@/composables/useNotification';
import { useTour } from '@/composables/useTour';
import { useNavigation } from '@/composables/useNavigation';

const { user, isAuthenticated, isAdmin, logout } = useAuth();
const router = useRouter();
const { success } = useNotification();
const { openTour } = useTour();
const { mainNavLinks, guestNavLinks } = useNavigation();

const visibleNavLinks = computed(() => mainNavLinks.filter((link) => !link.adminOnly || isAdmin.value));

const isNavOpen = ref(false);
const isScrolled = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 8;
};

onMounted(() => {
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const handleOpenTour = () => {
    closeNav();
    openTour(0);
};

const toggleNav = () => {
    isNavOpen.value = !isNavOpen.value;
};

const closeNav = () => {
    isNavOpen.value = false;
};

const handleLogout = async () => {
    closeNav();
    await logout();
    success('সফলভাবে লগআউট হয়েছেন।');
    router.push('/login');
};
</script>

<template>
    <nav class="navbar navbar-expand-lg sticky-top modern-navbar" :class="{ 'is-scrolled': isScrolled }">
        <div class="container">
            <router-link to="/" class="navbar-brand d-flex align-items-center gap-2 fw-bold" @click="closeNav">
                <span class="brand-icon bg-primary text-white d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-brain"></i>
                </span>
                <span class="brand-text">Amar<span class="text-primary">SocialContent</span></span>
            </router-link>

            <button
                class="navbar-toggler border-0 p-1 shadow-none"
                type="button"
                aria-label="Toggle navigation"
                aria-controls="navbarContent"
                :aria-expanded="isNavOpen ? 'true' : 'false'"
                @click="toggleNav"
            >
                <i class="bi fs-4" :class="isNavOpen ? 'bi-x-lg' : 'bi-list'"></i>
            </button>

            <div
                class="collapse navbar-collapse"
                :class="{ 'show': isNavOpen }"
                id="navbarContent"
            >
                <ul v-if="isAuthenticated" class="navbar-nav mx-lg-auto mb-2 mb-lg-0 gap-1 py-2 py-lg-0">
                    <li v-for="link in visibleNavLinks" :key="link.label" class="nav-item">
                        <router-link
                            :to="link.to"
                            class="nav-link nav-pill-link"
                            :class="link.linkClass"
                            active-class="active"
                            @click="closeNav"
                        >
                            <i class="bi" :class="link.icon"></i>
                            <span>{{ link.label }}</span>
                        </router-link>
                    </li>
                </ul>

                <ul v-else class="navbar-nav mx-lg-auto mb-2 mb-lg-0 gap-1 py-2 py-lg-0">
                    <li v-for="link in guestNavLinks" :key="link.label" class="nav-item">
                        <router-link
                            :to="link.to"
                            class="nav-link nav-pill-link"
                            @click="closeNav"
                        >
                            <i class="bi" :class="link.icon"></i>
                            <span>{{ link.label }}</span>
                        </router-link>
                    </li>
                </ul>

                <div class="navbar-actions d-flex align-items-center flex-wrap gap-2 ms-lg-auto pt-2 pt-lg-0">
                    <template v-if="isAuthenticated">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            title="প্ল্যাটফর্ম ট্যুর গাইড দেখুন"
                            @click="handleOpenTour"
                        >
                            <i class="bi bi-compass"></i>
                            <span class="d-none d-md-inline">ট্যুর গাইড</span>
                        </button>

                        <router-link
                            to="/profile"
                            class="user-profile-pill d-flex align-items-center gap-2 text-decoration-none"
                            title="প্রোফাইল ও নিজস্ব AI API Key পরিচালনা করুন"
                            @click="closeNav"
                        >
                            <div class="user-avatar bg-primary text-white d-flex align-items-center justify-content-center fw-bold">
                                {{ (user?.name || user?.email || 'U')[0].toUpperCase() }}
                            </div>
                            <div class="text-start leading-tight">
                                <div class="fw-semibold small text-truncate user-name">
                                    {{ user?.name || 'ব্যবহারকারী' }}
                                </div>
                                <div class="smaller text-truncate user-email">
                                    {{ user?.email }}
                                </div>
                            </div>
                            <i class="bi bi-gear smaller"></i>
                        </router-link>

                        <button
                            class="btn btn-outline-danger btn-sm"
                            title="অ্যাকাউন্ট থেকে লগআউট করুন"
                            @click="handleLogout"
                        >
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="d-none d-sm-inline">লগআউট</span>
                        </button>
                    </template>

                    <template v-else>
                        <router-link to="/login" class="btn btn-soft btn-sm px-3" @click="closeNav">
                            লগইন
                        </router-link>
                        <router-link to="/login" class="btn btn-primary btn-sm px-4" @click="closeNav">
                            শুরু করুন
                        </router-link>
                    </template>
                </div>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.modern-navbar {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border-default);
    padding-top: 0.85rem;
    padding-bottom: 0.85rem;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.modern-navbar.is-scrolled {
    box-shadow: 0 10px 30px -22px rgba(28, 33, 30, 0.45);
}

.navbar-brand {
    color: var(--text-primary);
}

.brand-icon {
    width: 38px;
    height: 38px;
    font-size: 1.05rem;
    border-radius: 50%;
    box-shadow: 0 6px 14px -10px rgba(var(--brand-primary-rgb), 0.9);
}

.brand-text {
    font-size: 1.12rem;
    letter-spacing: -0.03em;
    color: var(--text-primary);
}

.nav-pill-link {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-secondary);
    padding: 0.5rem 1rem;
    border-radius: var(--radius-pill);
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
    transition: color 0.15s ease, background-color 0.15s ease;
}

.nav-pill-link:hover {
    color: var(--text-primary);
    background-color: var(--surface-sunken);
}

.nav-pill-link.active {
    color: var(--brand-primary) !important;
    font-weight: 600;
    background-color: var(--brand-soft);
    box-shadow: inset 0 0 0 1px rgba(var(--brand-primary-rgb), 0.12);
}

.navbar-toggler:focus-visible {
    box-shadow: 0 0 0 4px rgba(var(--brand-primary-rgb), 0.18);
    border-radius: var(--radius-pill);
}

.admin-link:hover,
.admin-link.active {
    color: #b91c1c !important;
    background-color: #fef2f2;
}

.navbar-actions {
    border-top: 1px solid var(--border-default);
}

.user-profile-pill {
    padding: 0.25rem 0.75rem 0.25rem 0.25rem;
    border-radius: var(--radius-pill);
    background: var(--surface-sunken);
    border: 1px solid var(--border-default);
    color: var(--text-primary);
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.user-profile-pill:hover {
    background: var(--brand-soft);
    border-color: var(--border-hover);
    color: var(--text-primary);
}

.user-avatar {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
    border-radius: 50%;
    flex-shrink: 0;
}

.user-name {
    max-width: 140px;
    color: var(--text-primary);
}

.user-email {
    max-width: 140px;
    color: var(--text-secondary);
}

.smaller {
    font-size: 0.75rem;
}

.leading-tight {
    line-height: 1.2;
}

@media (min-width: 992px) {
    .navbar-actions {
        border-top: 0;
    }
}

@media (max-width: 991.98px) {
    .modern-navbar {
        background: var(--surface-card);
    }

    .navbar-collapse {
        background: var(--surface-card);
        border: 1px solid var(--border-default);
        border-radius: var(--radius-card);
        box-shadow: var(--card-shadow);
        padding: 0.75rem;
        margin-top: 0.85rem;
    }

    .nav-pill-link {
        padding: 0.65rem 0.9rem;
        width: 100%;
    }

    .user-profile-pill {
        width: 100%;
    }

    .navbar-actions {
        border-top: 1px dashed var(--border-default);
        margin-top: 0.5rem;
        padding-top: 0.85rem;
    }

    .navbar-actions .btn {
        width: 100%;
    }
}
</style>
