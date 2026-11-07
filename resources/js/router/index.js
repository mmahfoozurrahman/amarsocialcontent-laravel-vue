import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { usePreloader } from '@/composables/usePreloader';

import LandingView from '@/views/LandingView.vue';
import LoginView from '@/views/auth/LoginView.vue';
import DashboardView from '@/views/user/DashboardView.vue';
import LibraryView from '@/views/user/LibraryView.vue';
import ArticleReaderView from '@/views/user/ArticleReaderView.vue';
import PublicArticleView from '@/views/public/PublicArticleView.vue';
import AdminDashboardView from '@/views/admin/AdminDashboardView.vue';
import CollectionsView from '@/views/user/second-brain/CollectionsView.vue';
import CollectionBuilderView from '@/views/user/second-brain/CollectionBuilderView.vue';
import NoteReaderView from '@/views/user/second-brain/NoteReaderView.vue';
import SearchView from '@/views/user/SearchView.vue';
import WatchHistoryView from '@/views/user/WatchHistoryView.vue';
import ProfileView from '@/views/user/ProfileView.vue';

const routes = [
    {
        path: '/',
        name: 'landing',
        component: LandingView,
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guestOnly: true },
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardView,
        meta: { requiresAuth: true },
    },
    {
        path: '/library',
        name: 'library',
        component: LibraryView,
        meta: { requiresAuth: true },
    },
    {
        path: '/articles/:id',
        name: 'article-reader',
        component: ArticleReaderView,
        meta: { requiresAuth: true },
    },
    {
        path: '/collections',
        name: 'collections',
        component: CollectionsView,
        meta: { requiresAuth: true },
    },
    {
        path: '/collections/:id/builder',
        name: 'collection-builder',
        component: CollectionBuilderView,
        meta: { requiresAuth: true },
    },
    {
        path: '/notes/:id',
        name: 'note-reader',
        component: NoteReaderView,
        meta: { requiresAuth: true },
    },
    {
        path: '/search',
        name: 'search',
        component: SearchView,
        meta: { requiresAuth: true },
    },
    {
        path: '/watch-history',
        name: 'watch-history',
        component: WatchHistoryView,
        meta: { requiresAuth: true },
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfileView,
        meta: { requiresAuth: true },
    },
    {
        path: '/p/:shareToken',
        name: 'public-article',
        component: PublicArticleView,
    },
    {
        path: '/admin',
        name: 'admin',
        component: AdminDashboardView,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to) {
        if (to.hash) {
            return { el: to.hash, behavior: 'smooth' };
        }

        return { top: 0 };
    },
});

// Navigation Guards with Preloader & Role Protection
router.beforeEach((to, from, next) => {
    const { start } = usePreloader();
    start();

    const { isAuthenticated, isAdmin } = useAuth();

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return next({ name: 'login', query: { redirect: to.fullPath } });
    }

    if (to.meta.guestOnly && isAuthenticated.value) {
        return next({ name: 'dashboard' });
    }

    if (to.meta.requiresAdmin && !isAdmin.value) {
        return next({ name: 'dashboard' });
    }

    next();
});

router.afterEach(() => {
    const { finish } = usePreloader();
    finish();
});

router.onError(() => {
    const { finish } = usePreloader();
    finish();
});

export default router;
