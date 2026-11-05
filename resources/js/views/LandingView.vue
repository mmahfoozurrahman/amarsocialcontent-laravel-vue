<script setup>
import { useAuth } from '@/composables/useAuth';

const { isAuthenticated } = useAuth();

const features = [
    {
        icon: 'bi-journal-text',
        title: 'বাংলা মেন্টর-স্টাইল সারসংক্ষেপ',
        text: 'ইংরেজি টেকনিক্যাল টার্ম অটুট রেখে ভিডিওর মূল কনসেপ্ট, সারসংক্ষেপ ও অ্যাকশনেবল স্টেপস খাঁটি বাংলায় গুছিয়ে লেখা আর্টিকেল।',
    },
    {
        icon: 'bi-mortarboard',
        title: 'ব্যক্তিগত LMS কোর্স বিল্ডার',
        text: 'কোর্স → চ্যাপ্টার → লেসন কাঠামোয় নিজের সিলেবাস সাজান, প্রগ্রেস ট্র্যাক করুন এবং আগের-পরের লেসনে সহজে যান।',
    },
    {
        icon: 'bi-search-heart',
        title: 'সিম্যান্টিক হাইব্রিড সার্চ',
        text: 'হুবহু কি-ওয়ার্ড মনে নেই? ভাবার্থ বুঝে সঠিক প্যারাগ্রাফ খুঁজে এনে প্রাসঙ্গিকতা স্কোরসহ হাইলাইট করে দেয়।',
    },
];

const stats = [
    { value: '৭৬৮', label: 'ডাইমেনশনাল ভেক্টর', hint: 'ভাবার্থ এমবেডিং' },
    { value: '৭০/৩০', label: 'ভেক্টর ও ফুলটেক্সট', hint: 'হাইব্রিড সার্চ অনুপাত' },
    { value: '৩', label: 'স্তরের শেখার কাঠামো', hint: 'কোর্স → চ্যাপ্টার → লেসন' },
    { value: '৭', label: 'ধাপের গাইডেড ট্যুর', hint: 'প্রথমবারেই পুরো সিস্টেম' },
];
</script>

<template>
    <div class="landing-page">
        <!-- Hero -->
        <section id="hero" class="hero-section">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill mb-3">
                            <i class="bi bi-stars me-1"></i> এআই-পাওয়ার্ড সোশ্যাল মিডিয়া নলেজ অ্যাগ্রিগেটর
                        </span>
                        <h1 class="hero-title fw-bold mb-3">
                            সোশ্যাল মিডিয়ার জ্ঞানকে রূপান্তর করুন <span class="text-primary">ব্যক্তিগত সেকেন্ড ব্রেনে</span>
                        </h1>
                        <p class="hero-lead mb-4">
                            ইউটিউব ভিডিও ও শর্টসের হারিয়ে যাওয়া মূল্যবান জ্ঞানকে বাংলায় সুসংগঠিত, সহজবোধ্য ও বিস্তারিত লার্নিং ম্যাটেরিয়ালে রূপান্তর করুন। কোর্স ও চ্যাপ্টারে সাজান এবং যেকোনো সময় সিম্যান্টিক সার্চের মাধ্যমে খুঁজে নিন।
                        </p>

                        <div class="d-flex flex-wrap gap-3 mb-4">
                            <router-link v-if="!isAuthenticated" to="/login" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-box-arrow-in-right"></i> পাসওয়ার্ডলেস লগইন / শুরু করুন
                            </router-link>
                            <router-link v-else to="/dashboard" class="btn btn-primary btn-lg px-4">
                                <i class="bi bi-speedometer2"></i> আপনার ড্যাশবোর্ডে যান
                            </router-link>
                            <a href="#features" class="btn btn-outline-primary btn-lg px-4">
                                <i class="bi bi-grid"></i> ফিচারগুলো দেখুন
                            </a>
                        </div>

                        <!-- Highlight Pills -->
                        <div class="row g-3 pt-4 hero-highlights">
                            <div class="col-sm-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="fw-semibold text-secondary small">মেন্টর-স্টাইল খাঁটি বাংলা</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="fw-semibold text-secondary small">ব্যক্তিগত LMS কোর্স বিল্ডার</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="fw-semibold text-secondary small">বুদ্ধিমান সিম্যান্টিক সার্চ</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card pipeline-card card-hover">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-3 pipeline-head">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="pipeline-brand-icon">
                                        <i class="bi bi-youtube"></i>
                                    </span>
                                    <span class="fw-bold small">ইউটিউব ভিডিও → বাংলা নলেজ</span>
                                </div>
                                <span class="badge bg-success-subtle text-success">সক্রিয় পাইপলাইন</span>
                            </div>

                            <div class="pipeline-step">
                                <p class="small text-secondary mb-1">ধাপ ১ — ইনপুট ভিডিও বা শর্টস লিঙ্ক</p>
                                <code class="text-primary small">youtube.com/shorts/sample123</code>
                            </div>

                            <div class="pipeline-arrow">
                                <i class="bi bi-arrow-down fs-5"></i>
                            </div>

                            <div class="pipeline-output">
                                <div class="d-flex align-items-center gap-2 text-primary mb-2">
                                    <i class="bi bi-journal-text"></i>
                                    <strong class="small">ধাপ ২ — বাংলা মেন্টর-স্টাইল আর্টিকেল</strong>
                                </div>
                                <p class="small text-secondary mb-0">
                                    "সহজভাবে বললে, এই ভিডিওতে আলোচিত মূল কনসেপ্টটি কীভাবে আপনার সফটওয়্যার আর্কিটেকচারে কাজে লাগবে..."
                                </p>
                            </div>

                            <div class="pipeline-arrow">
                                <i class="bi bi-arrow-down fs-5"></i>
                            </div>

                            <div class="pipeline-step">
                                <p class="small text-secondary mb-2">ধাপ ৩ — সাজান, খুঁজুন ও শেয়ার করুন</p>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="pipeline-chip"><i class="bi bi-mortarboard"></i> কোর্সে যোগ করুন</span>
                                    <span class="pipeline-chip"><i class="bi bi-search-heart"></i> সার্চে খুঁজুন</span>
                                    <span class="pipeline-chip"><i class="bi bi-share"></i> শেয়ার করুন</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="section-block">
            <div class="container">
                <div class="section-heading text-center">
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">মূল ফিচার</span>
                    <h2 class="section-title fw-bold mb-2">তিনটি ধাপে জ্ঞান আপনার নিয়ন্ত্রণে</h2>
                    <p class="text-secondary mb-0">ভিডিও ইনজেস্ট থেকে গুছানো আর্টিকেল, কোর্স ও সার্চ — সব একই প্ল্যাটফর্মে।</p>
                </div>

                <div class="row g-4">
                    <div v-for="feature in features" :key="feature.title" class="col-md-6 col-lg-4">
                        <div class="card feature-card card-hover h-100 p-4">
                            <span class="feature-icon">
                                <i class="bi" :class="feature.icon"></i>
                            </span>
                            <h3 class="h5 fw-bold mt-3 mb-2">{{ feature.title }}</h3>
                            <p class="text-secondary small mb-0">{{ feature.text }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stat Band -->
        <section id="stats" class="section-block pt-0">
            <div class="container">
                <div class="brand-stat-band p-4 p-lg-5">
                    <div class="row g-4">
                        <div v-for="stat in stats" :key="stat.label" class="col-6 col-lg-3 stat-item">
                            <div class="stat-value">{{ stat.value }}</div>
                            <div class="stat-label">{{ stat.label }}</div>
                            <div class="stat-hint">{{ stat.hint }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quote -->
        <section id="why-us" class="section-block pt-0">
            <div class="container">
                <div class="quote-block text-center mx-auto">
                    <i class="bi bi-quote quote-mark"></i>
                    <p class="quote-text fw-semibold mb-0">
                        ভিডিও দেখা মানে জ্ঞান হারানো নয় — দেখুন, বাংলায় পড়ুন, কোর্সে সাজান, যেকোনো দিন খুঁজে নিন।
                    </p>
                </div>
            </div>
        </section>

        <!-- Closing CTA -->
        <section id="get-started" class="section-block pt-0">
            <div class="container">
                <div class="brand-cta-band p-4 p-lg-5 text-center">
                    <h2 class="section-title fw-bold mb-2">আজই আপনার সেকেন্ড ব্রেন গড়ে তুলুন</h2>
                    <p class="text-secondary mb-4 cta-text mx-auto">
                        ইমেইল দিয়ে পাসওয়ার্ডলেস লগইন করুন, নিজের এআই কী যোগ করুন এবং প্রথম ভিডিওটি বাংলা আর্টিকেলে রূপান্তর করুন।
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <router-link v-if="!isAuthenticated" to="/login" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-box-arrow-in-right"></i> শুরু করুন
                        </router-link>
                        <router-link v-else to="/dashboard" class="btn btn-primary btn-lg px-4">
                            <i class="bi bi-speedometer2"></i> ড্যাশবোর্ডে যান
                        </router-link>
                        <router-link v-if="!isAuthenticated" to="/login" class="btn btn-outline-primary btn-lg px-4">
                            লগইন
                        </router-link>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.landing-page {
    padding-bottom: 1rem;
}

section[id] {
    scroll-margin-top: 5.5rem;
}

.hero-section {
    padding: 3.5rem 0 3rem;
}

.hero-title {
    font-size: clamp(2rem, 4.2vw, 3.1rem);
    line-height: 1.18;
    letter-spacing: -0.03em;
    color: var(--text-primary);
}

.hero-lead {
    max-width: 56ch;
    font-size: 1.075rem;
    line-height: 1.75;
    color: var(--text-secondary);
}

.hero-highlights {
    border-top: 1px solid var(--border-default);
}

.pipeline-card {
    padding: 1.5rem;
}

.pipeline-head {
    border-bottom: 1px solid var(--border-default);
}

.pipeline-brand-icon {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-pill);
    background: #dc2626;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.pipeline-step {
    background: var(--surface-sunken);
    border-radius: var(--radius-control);
    padding: 0.9rem 1rem;
}

.pipeline-arrow {
    display: flex;
    justify-content: center;
    padding: 0.35rem 0;
    color: var(--brand-primary);
}

.pipeline-output {
    background: var(--surface-card);
    border: 1px solid var(--brand-primary);
    border-radius: var(--radius-control);
    padding: 1rem;
}

.pipeline-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--brand-primary);
    background: var(--brand-soft);
    border-radius: var(--radius-pill);
    padding: 0.25rem 0.7rem;
}

.section-block {
    padding: 3rem 0;
}

.section-heading {
    max-width: 46rem;
    margin: 0 auto 2.5rem;
}

.section-title {
    font-size: clamp(1.5rem, 2.6vw, 2.1rem);
    line-height: 1.3;
    letter-spacing: -0.03em;
    color: var(--text-primary);
}

.feature-card {
    background: var(--surface-card);
    border: 1px solid var(--border-default);
}

.feature-icon {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-control);
    background: var(--brand-soft);
    color: var(--brand-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.quote-block {
    max-width: 52rem;
    padding: 2rem 1rem;
}

.quote-mark {
    display: block;
    font-size: 2.5rem;
    line-height: 1;
    color: var(--brand-accent);
}

.quote-text {
    font-size: clamp(1.15rem, 2.2vw, 1.6rem);
    line-height: 1.6;
    letter-spacing: -0.02em;
    color: var(--text-primary);
}

.cta-text {
    max-width: 46ch;
}

@media (max-width: 991.98px) {
    .hero-section {
        padding: 2.25rem 0 2rem;
    }

    .section-block {
        padding: 2.25rem 0;
    }
}
</style>

