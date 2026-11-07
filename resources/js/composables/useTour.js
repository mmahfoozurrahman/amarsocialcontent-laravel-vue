import { ref } from 'vue';

const isOpen = ref(false);
const currentStep = ref(0);

const steps = [
    {
        id: 'welcome',
        badge: 'প্ল্যাটফর্ম পরিচিতি',
        badgeColor: 'primary',
        icon: 'bi-stars',
        iconColor: 'text-primary',
        iconBg: 'bg-primary-subtle',
        title: 'আমারসোশ্যাল-এ আপনাকে স্বাগতম! 🚀',
        description: 'এটি একটি এআই-চালিত মাল্টি-টেন্যান্ট সোশ্যাল নলেজ প্ল্যাটফর্ম। আপনি যেসব ইউটিউব ভিডিও বা শর্টস দেখেন, সেগুলো ভুলে না গিয়ে বাংলায় উচ্চমানের শিক্ষণীয় আর্টিকেলে রূপান্তর করে নিজস্ব লাইব্রেরিতে সংরক্ষণ করার এক অভিনব মাধ্যম।',
        tips: [
            'ইংরেজি টেকনিক্যাল টার্মস ঠিক রেখে সহজ ও প্রাঞ্জল বাংলায় নোট তৈরি করে।',
            'ভিডিওর মূল শিক্ষা, সামারি ও অ্যাকশনেবল স্টেপস গুছিয়ে দেয়।'
        ],
        actionRoute: null,
        actionLabel: null,
    },
    {
        id: 'byok',
        badge: 'প্রাইভেসি ও এআই সেটআপ',
        badgeColor: 'warning',
        icon: 'bi-key-fill',
        iconColor: 'text-warning',
        iconBg: 'bg-warning-subtle',
        title: 'নিজস্ব এআই কী সেটআপ (BYOK)',
        description: 'প্ল্যাটফর্মটি "Bring Your Own Key" মডেলে চলে। এর মানে প্রতিটি ব্যবহারকারী নিজের Groq বা Google Gemini API Key ব্যবহার করে কনটেন্ট জেনারেট করেন। আপনার ডেটা ও এআই কোটা সম্পূর্ণ আপনার নিয়ন্ত্রণে থাকে।',
        tips: [
            'API Key ডেটাবেজে মিলিটারী-গ্রেড AES-256 এনক্রিপ্ট হয়ে সংরক্ষিত থাকে।',
            'প্রোফাইলে গিয়ে "কানেকশন টেস্ট করুন" বাটনে ক্লিক করে কী পরীক্ষা করতে পারবেন।'
        ],
        actionRoute: '/profile',
        actionLabel: 'প্রোফাইল সেটিংসে যান',
    },
    {
        id: 'add_content',
        badge: 'ইনজেশন ও এআই জেনারেশন',
        badgeColor: 'danger',
        icon: 'bi-youtube',
        iconColor: 'text-danger',
        iconBg: 'bg-danger-subtle',
        title: 'এক ক্লিকে ইউটিউব ভিডিও বা শর্টস ইনজেস্ট',
        description: 'ড্যাশবোর্ডের "+ নতুন কনটেন্ট" বাটনে গিয়ে যেকোনো ইউটিউব লিংক পেস্ট করুন। ব্যস! আমাদের ব্যাকগ্রাউন্ড কিউ স্বয়ংক্রিয়ভাবে মেটাডাটা ও ক্যাপশন এনে এআই দিয়ে বাংলায় সম্পূর্ণ আর্টিকেল তৈরি করবে।',
        tips: [
            'ভিডিওর ক্যাপশন না থাকলে আপনি ম্যানুয়ালি ট্রান্সক্রিপ্ট ইনপুট দিতে পারবেন।',
            'ইউটিউব চ্যানেলের নাম স্বয়ংক্রিয়ভাবে ট্যাগ হিসেবে যুক্ত হয় (#freeCodeCamp, ইত্যাদি)।'
        ],
        actionRoute: '/dashboard',
        actionLabel: 'ড্যাশবোর্ডে দেখুন',
    },
    {
        id: 'library_reader',
        badge: 'নলেজ লাইব্রেরি ও রিডার',
        badgeColor: 'success',
        icon: 'bi-journal-bookmark-fill',
        iconColor: 'text-success',
        iconBg: 'bg-success-subtle',
        title: 'পার্সোনাল লাইব্রেরি, ইনলাইন এডিটর ও নোটস',
        description: 'আপনার জেনারেট হওয়া সব আর্টিকেল সুন্দর কার্ড ভিউতে সাজানো থাকে। আর্টিকেল রিডারে আছে বিল্ট-ইন ইউটিউব প্লেয়ার (১৬:৯ ভিডিও ও ৯:১৬ শর্টস), লাইভ ইনলাইন টাইটেল ও কনটেন্ট এডিটর এবং ব্যক্তিগত স্টাডি নোট রাখার ড্রয়ার।',
        tips: [
            'স্টাডি টাইমার স্বয়ংক্রিয়ভাবে আপনি কত মিনিট পড়াশোনা করেছেন তা ট্র্যাক করে।',
            'সিকিউর ৩২-অক্ষরের টোকেন দিয়ে যেকোনো আর্টিকেল পাবলিকলি বন্ধুদের সাথে শেয়ার করতে পারেন।'
        ],
        actionRoute: '/library',
        actionLabel: 'লাইব্রেরি ঘুরে দেখুন',
    },
    {
        id: 'lms_collections',
        badge: 'সেকেন্ড ব্রেন অর্গানাইজেশন',
        badgeColor: 'info',
        icon: 'bi-mortarboard-fill',
        iconColor: 'text-info',
        iconBg: 'bg-info-subtle',
        title: 'কালেকশনস, সেকশনস ও নোটস স্ট্রাকচার',
        description: 'এলোমেলো কনটেন্টের পরিবর্তে আপনার শেখার বিষয়গুলোকে সুন্দর কালেকশনসে সাজান। "কালেকশনস ও নোটস" পেজে গিয়ে কালেকশনস তৈরি করুন, সেকশনসে ভাগ করুন এবং পূর্বে জেনারেট করা আর্টিকেলগুলোকে নোটস হিসেবে যুক্ত করুন।',
        tips: [
            'সম্পূর্ণ সিলেবাস আকারে আগের ও পরের নোটসে যাওয়ার ইন্টারঅ্যাক্টিভ নেভিগেশন।',
            'কালেকশনস সমাপ্তির প্রগ্রেস ট্র্যাক করার সুবিধা।'
        ],
        actionRoute: '/collections',
        actionLabel: 'কালেকশনস পেজে যান',
    },
    {
        id: 'semantic_search',
        badge: 'স্মার্ট সার্চ ইঞ্জিন',
        badgeColor: 'primary',
        icon: 'bi-search-heart-fill',
        iconColor: 'text-primary',
        iconBg: 'bg-primary-subtle',
        title: 'এআই সেম্যান্টিক ও হাইব্রিড সার্চ',
        description: 'হুবহু কি-ওয়ার্ড মনে নেই? কোনো সমস্যা নেই! বাংলা বা ইংরেজিতে যেকোনো কনসেপ্ট লিখুন। আমাদের ৭০% ভেক্টর এমবেডিং ও ৩০% ফুলটেক্সট হাইব্রিড ইঞ্জিন স্বয়ংক্রিয়ভাবে সঠিক প্যারাগ্রাফ খুঁজে এনে হাইলাইট করে দেবে।',
        tips: [
            '৭৬৮-ডাইমেনশনাল ভেক্টর দিয়ে ভাবার্থ (Semantic) বিশ্লেষণ।',
            'রিসেন্ট সার্চ হিস্ট্রি ও ফিল্টার পিলস দিয়ে নিমিষে তথ্য পাওয়ার সুবিধা।'
        ],
        actionRoute: '/search',
        actionLabel: 'সার্চ করে দেখুন',
    },
    {
        id: 'takeout_import',
        badge: 'এক ক্লিকে হিস্ট্রি মাইগ্রেশন',
        badgeColor: 'secondary',
        icon: 'bi-clock-history',
        iconColor: 'text-secondary',
        iconBg: 'bg-secondary-subtle',
        title: 'গুগল টেকআউট ওয়াচ হিস্ট্রি ইম্পোর্ট',
        description: 'ইউটিউব থেকে আপনার সম্পূর্ণ ওয়াচ হিস্ট্রি ফাইলটি (HTML বা JSON) আপলোড করুন। আমাদের উইজার্ড প্রিয় চ্যানেলগুলো ফিল্টার করে দেবে এবং আপনি এক ক্লিকে প্রয়োজনীয় ভিডিওগুলোকে আপনার লাইব্রেরিতে নিয়ে আসতে পারবেন।',
        tips: [
            'স্মার্ট ডুপ্লিকেট ডিটেক্টর—আগে ইম্পোর্ট করা থাকলে তা চিহ্নিত করে দেয়।',
            'ব্যাচ কিউয়িং সিস্টেম—একসাথে একাধিক ভিডিও কিউতে পাঠানো যায়।'
        ],
        actionRoute: '/watch-history',
        actionLabel: 'হিস্ট্রি ইম্পোর্টে যান',
    },
];

export function useTour() {
    const getStorageKey = (userId) => `amar_social_tour_seen_${userId || 'guest'}`;

    const shouldAutoShow = (userId) => {
        if (!userId) return false;
        return !localStorage.getItem(getStorageKey(userId));
    };

    const markTourAsSeen = (userId) => {
        if (userId) {
            localStorage.setItem(getStorageKey(userId), 'true');
        }
    };

    const resetTourSeen = (userId) => {
        if (userId) {
            localStorage.removeItem(getStorageKey(userId));
        }
    };

    const openTour = (stepIndex = 0) => {
        currentStep.value = Math.max(0, Math.min(stepIndex, steps.length - 1));
        isOpen.value = true;
    };

    const closeTour = (userId = null) => {
        isOpen.value = false;
        if (userId) {
            markTourAsSeen(userId);
        }
    };

    const nextStep = () => {
        if (currentStep.value < steps.length - 1) {
            currentStep.value++;
        }
    };

    const prevStep = () => {
        if (currentStep.value > 0) {
            currentStep.value--;
        }
    };

    const goToStep = (index) => {
        if (index >= 0 && index < steps.length) {
            currentStep.value = index;
        }
    };

    return {
        isOpen,
        currentStep,
        steps,
        openTour,
        closeTour,
        nextStep,
        prevStep,
        goToStep,
        shouldAutoShow,
        markTourAsSeen,
        resetTourSeen,
    };
}

