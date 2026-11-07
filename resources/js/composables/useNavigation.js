/**
 * Single source of truth for the app's primary navigation links.
 * Shared by the top navbar (AppNavbar.vue) and the site footer (App.vue).
 */
export const mainNavLinks = [
    { to: '/dashboard', label: 'ড্যাশবোর্ড', icon: 'bi-speedometer2' },
    { to: '/library', label: 'লাইব্রেরি', icon: 'bi-journal-richtext' },
    { to: '/collections', label: 'সেকেন্ড ব্রেন', icon: 'bi-brain' },
    { to: '/search', label: 'এআই সার্চ', icon: 'bi-stars' },
    { to: '/watch-history', label: 'হিস্ট্রি ইমপোর্ট', icon: 'bi-clock-history' },
    { to: '/admin', label: 'অ্যাডমিন', icon: 'bi-shield-lock', adminOnly: true, linkClass: 'admin-link' },
];

export const guestNavLinks = [
    { to: { path: '/', hash: '#features' }, label: 'ফিচার', icon: 'bi-grid' },
    { to: { path: '/', hash: '#why-us' }, label: 'কেন আমরা', icon: 'bi-lightbulb' },
];

export function useNavigation() {
    return {
        mainNavLinks,
        guestNavLinks,
    };
}
