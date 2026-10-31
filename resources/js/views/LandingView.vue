<script setup>
import { useAuth } from '@/composables/useAuth';
import { useRouter } from 'vue-router';

const { isAuthenticated } = useAuth();
const router = useRouter();
</script>

<template>
    <div class="landing-page py-5">
        <div class="container">
            <div class="row align-items-center min-vh-75 py-5">
                <div class="col-lg-7">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill mb-3">
                        <i class="bi bi-stars me-1"></i> এআই-পাওয়ার্ড সোশ্যাল মিডিয়া নলেজ অ্যাগ্রিগেটর
                    </span>
                    <h1 class="display-4 fw-bold text-dark mb-3">
                        সোশ্যাল মিডিয়ার জ্ঞানকে রূপান্তর করুন <span class="text-primary">ব্যক্তিগত সেকেন্ড ব্রেনে</span>
                    </h1>
                    <p class="lead text-secondary mb-4">
                        ইউটিউব ভিডিও ও শর্টসের হারিয়ে যাওয়া মূল্যবান জ্ঞানকে বাংলায় সুসংগঠিত, সহজবোধ্য ও বিস্তারিত লার্নিং ম্যাটেরিয়ালে রূপান্তর করুন। কোর্স ও চ্যাপ্টারে সাজান এবং যেকোনো সময় সিম্যান্টিক সার্চের মাধ্যমে খুঁজে নিন।
                    </p>

                    <div class="d-flex flex-wrap gap-3 mb-5">
                        <router-link v-if="!isAuthenticated" to="/login" class="btn btn-primary btn-lg px-4 py-2">
                            <i class="bi bi-box-arrow-in-right me-2"></i> পাসওয়ার্ডলেস লগইন / শুরু করুন
                        </router-link>
                        <router-link v-else to="/dashboard" class="btn btn-primary btn-lg px-4 py-2">
                            <i class="bi bi-speedometer2 me-2"></i> আপনার ড্যাশবোর্ডে যান
                        </router-link>
                    </div>

                    <!-- Highlight Pills -->
                    <div class="row g-3 pt-3 border-top">
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

                <div class="col-lg-5 mt-5 mt-lg-0">
                    <div class="card border-0 shadow-lg p-4 bg-white rounded-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="bg-danger text-white rounded p-1 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="bi bi-youtube"></i>
                                </span>
                                <span class="fw-bold small">YouTube to Knowledge</span>
                            </div>
                            <span class="badge bg-success-subtle text-success">Active Pipeline</span>
                        </div>
                        <div class="p-3 bg-light rounded-3 mb-3">
                            <p class="small text-muted mb-1">ইনপুট ইউটিউব লিঙ্ক:</p>
                            <code class="text-primary small">youtube.com/shorts/sample123</code>
                        </div>
                        <div class="d-flex align-items-center justify-content-center my-2 text-primary">
                            <i class="bi bi-arrow-down fs-4"></i>
                        </div>
                        <div class="p-3 border rounded-3 bg-white">
                            <div class="d-flex align-items-center gap-2 text-primary mb-2">
                                <i class="bi bi-journal-text"></i>
                                <strong class="small">বাংলা মেন্টর-স্টাইল সারসংক্ষেপ</strong>
                            </div>
                            <p class="small text-secondary mb-0">
                                "সহজভাবে বললে, এই ভিডিওতে আলোচিত মূল কনসেপ্টটি কীভাবে আপনার সফটওয়্যার আর্কিটেকচারে কাজে লাগবে..."
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

