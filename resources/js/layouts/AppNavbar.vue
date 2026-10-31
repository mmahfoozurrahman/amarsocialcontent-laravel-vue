<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useNotification } from '@/composables/useNotification';
import { useTour } from '@/composables/useTour';

const { user, isAuthenticated, isAdmin, logout } = useAuth();
const router = useRouter();
const { success } = useNotification();
const { openTour } = useTour();

const isNavOpen = ref(false);

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
    <nav class="navbar navbar-expand-lg sticky-top modern-navbar">
        <div class="container">
            <!-- Brand -->
            <router-link to="/" class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark" @click="closeNav">
                <span class="brand-icon bg-primary text-white rounded-3 d-inline-flex align-items-center justify-content-center shadow-xs">
                    <i class="bi bi-brain"></i>
                </span>
                <span class="brand-text">Amar<span class="text-primary">SocialContent</span></span>
            </router-link>

            <!-- Mobile Hamburger Toggle Button -->
            <button
                class="navbar-toggler border-0 p-1 text-dark shadow-none"
                type="button"
                aria-label="Toggle navigation"
                @click="toggleNav"
            >
                <i class="bi fs-4" :class="isNavOpen ? 'bi-x-lg text-danger' : 'bi-list'"></i>
            </button>

            <!-- Navbar Links & Profile Collapse Menu -->
            <div
                class="collapse navbar-collapse"
                :class="{ 'show': isNavOpen }"
                id="navbarContent"
            >
                <ul v-if="isAuthenticated" class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1 py-2 py-lg-0">
                    <li class="nav-item">
                        <router-link
                            to="/dashboard"
                            class="nav-link nav-pill-link"
                            active-class="active"
                            @click="closeNav"
                        >
                            <i class="bi bi-speedometer2"></i>
                            <span>ড্যাশবোর্ড</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link
                            to="/library"
                            class="nav-link nav-pill-link"
                            active-class="active"
                            @click="closeNav"
                        >
                            <i class="bi bi-journal-richtext"></i>
                            <span>লাইব্রেরি</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link
                            to="/courses"
                            class="nav-link nav-pill-link"
                            active-class="active"
                            @click="closeNav"
                        >
                            <i class="bi bi-mortarboard"></i>
                            <span>কোর্স ও লেসন</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link
                            to="/search"
                            class="nav-link nav-pill-link"
                            active-class="active"
                            @click="closeNav"
                        >
                            <i class="bi bi-stars"></i>
                            <span>এআই সার্চ</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link
                            to="/watch-history"
                            class="nav-link nav-pill-link"
                            active-class="active"
                            @click="closeNav"
                        >
                            <i class="bi bi-clock-history"></i>
                            <span>হিস্ট্রি ইমপোর্ট</span>
                        </router-link>
                    </li>
                    <li v-if="isAdmin" class="nav-item">
                        <router-link
                            to="/admin"
                            class="nav-link nav-pill-link admin-link"
                            active-class="active text-danger"
                            @click="closeNav"
                        >
                            <i class="bi bi-shield-lock"></i>
                            <span>অ্যাডমিন</span>
                        </router-link>
                    </li>
                </ul>

                <!-- Right Side User Profile & Logout -->
                <div class="d-flex align-items-center gap-2 ms-auto pt-2 pt-lg-0 border-top border-lg-0">
                    <template v-if="isAuthenticated">
                        <!-- Platform Tour Button -->
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1.5 d-flex align-items-center gap-1.5 shadow-xs border text-dark bg-white"
                            title="প্ল্যাটফর্ম ট্যুর গাইড দেখুন"
                            @click="handleOpenTour"
                        >
                            <i class="bi bi-compass text-primary"></i>
                            <span class="d-none d-md-inline small fw-medium">ট্যুর গাইড</span>
                        </button>

                        <!-- User Profile Pill (Clickable link to /profile) -->
                        <router-link
                            to="/profile"
                            class="user-profile-pill d-flex align-items-center gap-2 p-1 pe-3 rounded-pill bg-light border text-decoration-none cursor-pointer shadow-xs"
                            title="প্রোফাইল ও নিজস্ব AI API Key পরিচালনা করুন"
                            @click="closeNav"
                        >
                            <div class="user-avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-xs">
                                {{ (user?.name || user?.email || 'U')[0].toUpperCase() }}
                            </div>
                            <div class="text-start leading-tight">
                                <div class="fw-semibold small text-dark text-truncate" style="max-width: 140px;">
                                    {{ user?.name || 'ব্যবহারকারী' }}
                                </div>
                                <div class="text-muted smaller text-truncate" style="max-width: 140px;">
                                    {{ user?.email }}
                                </div>
                            </div>
                            <i class="bi bi-gear-fill text-muted smaller ms-1"></i>
                        </router-link>

                        <!-- Logout Button -->
                        <button
                            class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1.5"
                            title="অ্যাকাউন্ট থেকে লগআউট করুন"
                            @click="handleLogout"
                        >
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="d-none d-sm-inline">লগআউট</span>
                        </button>
                    </template>

                    <template v-else>
                        <router-link to="/login" class="btn btn-primary btn-sm rounded-pill px-4 py-2" @click="closeNav">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>লগইন / শুরু করুন</span>
                        </router-link>
                    </template>
                </div>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.modern-navbar {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(226, 232, 240, 0.85);
    padding-top: 0.55rem;
    padding-bottom: 0.55rem;
    transition: all 0.2s ease;
}

.brand-icon {
    width: 34px;
    height: 34px;
    font-size: 1.15rem;
}

.brand-text {
    font-size: 1.15rem;
    letter-spacing: -0.02em;
}

.nav-pill-link {
    font-size: 0.9rem;
    font-weight: 500;
    color: #475569;
    padding: 0.45rem 0.85rem;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.15s ease-in-out;
}

.nav-pill-link:hover {
    color: #0f172a;
    background-color: #f1f5f9;
}

.nav-pill-link.active {
    color: #2563eb !important;
    font-weight: 600;
    background-color: #eff6ff;
}

.admin-link:hover {
    color: #dc2626 !important;
    background-color: #fef2f2;
}

.user-profile-pill {
    transition: all 0.15s ease-in-out;
}

.user-avatar {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}

.smaller {
    font-size: 0.75rem;
}

.leading-tight {
    line-height: 1.2;
}

.shadow-xs {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

@media (max-width: 991.98px) {
    .modern-navbar {
        background: #ffffff;
    }
    .nav-pill-link {
        padding: 0.6rem 0.85rem;
        width: 100%;
    }
    .user-profile-pill {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>
