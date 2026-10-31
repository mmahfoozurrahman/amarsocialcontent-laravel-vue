<script setup>
import { ref, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useNotification } from '@/composables/useNotification';

const router = useRouter();
const route = useRoute();
const { sendOtp, verifyOtp, isAuthenticating } = useAuth();
const { success, error } = useNotification();

// Environment & Demo Mode
const isDemoMode = ref(window.AMAR_CONFIG?.isDemoMode ?? true);
const demoOtp = ref('');
const copied = ref(false);

// Form State
const step = ref(1); // 1 = Email submission, 2 = OTP verification
const email = ref('');
const name = ref('');
const otp = ref('');
const errors = ref({});

// Cooldown Timer
const countdown = ref(0);
let timerInterval = null;

const startCooldown = (seconds = 60) => {
    countdown.value = seconds;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--;
        } else {
            clearInterval(timerInterval);
        }
    }, 1000);
};

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// Step 1: Request OTP
const handleSendOtp = async () => {
    errors.value = {};
    if (!email.value) {
        errors.value.email = 'অনুগ্রহ করে আপনার ইমেইল এড্রেস লিখুন।';
        return;
    }

    try {
        const response = await sendOtp(email.value, name.value);
        success(response.message || 'আপনার ওটিপি কোড প্রস্তুত হয়েছে।');
        step.value = 2;
        startCooldown(response.cooldown_seconds || (response.demo_mode ? 10 : 60));

        // In demo mode or local environment, save and autofill the OTP
        if (response.debug_otp) {
            demoOtp.value = response.debug_otp;
            otp.value = response.debug_otp;
        } else {
            demoOtp.value = '';
        }
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.errors) {
            errors.value = Object.fromEntries(
                Object.entries(err.response.data.errors).map(([k, v]) => [k, v[0]])
            );
        } else {
            error(err.response?.data?.message || 'ওটিপি পাঠাতে সমস্যা হয়েছে। পুনরায় চেষ্টা করুন।');
        }
    }
};

// Step 2: Verify OTP
const handleVerifyOtp = async () => {
    errors.value = {};
    if (!otp.value || otp.value.length !== 6) {
        errors.value.otp = 'সঠিক ৬-সংখ্যার ওটিপি কোড লিখুন।';
        return;
    }

    try {
        const response = await verifyOtp(email.value, otp.value, name.value);
        success(response.message || 'সফলভাবে লগইন হয়েছে!');

        const redirect = route.query.redirect || '/dashboard';
        router.push(redirect);
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.errors) {
            errors.value = Object.fromEntries(
                Object.entries(err.response.data.errors).map(([k, v]) => [k, v[0]])
            );
        } else {
            error(err.response?.data?.message || 'ভেরিফিকেশন সম্পন্ন হয়নি। আবার চেষ্টা করুন।');
        }
    }
};

// Resend OTP
const handleResend = () => {
    if (countdown.value > 0 || isAuthenticating.value) return;
    otp.value = '';
    handleSendOtp();
};

const backToStepOne = () => {
    step.value = 1;
    otp.value = '';
    errors.value = {};
};

const autoFillOtp = () => {
    if (demoOtp.value) {
        otp.value = demoOtp.value;
    }
};

const copyDemoOtp = async () => {
    if (demoOtp.value) {
        try {
            await navigator.clipboard.writeText(demoOtp.value);
            copied.value = true;
            setTimeout(() => { copied.value = false; }, 2000);
        } catch {
            // Fallback
            copied.value = true;
            setTimeout(() => { copied.value = false; }, 2000);
        }
    }
};
</script>

<template>
    <div class="auth-page py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 56px; height: 56px;">
                                <i :class="step === 1 ? 'bi-envelope-check fs-3' : 'bi-shield-lock fs-3'"></i>
                            </div>
                            <h3 class="fw-bold text-dark">
                                {{ step === 1 ? 'পাসওয়ার্ডলেস লগইন / সাইনআপ' : 'ওটিপি ভেরিফিকেশন' }}
                            </h3>
                            <p class="text-secondary small mb-0">
                                {{ step === 1 
                                    ? 'পাসওয়ার্ড মনে রাখার ঝামেলা নেই! আপনার ইমেইল দিন, আমরা একটি ওটিপি পাঠাবো।' 
                                    : `${email} ঠিকানায় পাঠানো ৬-সংখ্যার কোডটি লিখুন।` }}
                            </p>
                        </div>

                        <!-- Step 1: Email Form -->
                        <form v-if="step === 1" @submit.prevent="handleSendOtp" novalidate>
                            <!-- Demo Mode Banner -->
                            <div v-if="isDemoMode" class="alert alert-primary bg-primary-subtle border-0 rounded-3 p-3 mb-3 text-start">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-primary text-white"><i class="bi bi-stars"></i> ডেমো মোড</span>
                                    <span class="small fw-bold text-primary">স্ক্রিন থেকেই ওটিপি পাওয়া যাবে</span>
                                </div>
                                <p class="small text-secondary mb-2">
                                    ইমেইল চেক করার ঝামেলা নেই! যে কোনো ইমেইল দিয়ে কোড পাঠালে ওটিপি সরাসরি পরের স্ক্রিনেই দেখতে পাবেন।
                                </p>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <span class="smaller text-muted me-1">ক্লিক করে পূরণ করুন:</span>
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-white bg-white border py-0 px-2 smaller fw-semibold text-primary"
                                        @click="email = 'mail@laravelcs.com'; name = 'Lara Code Vault'"
                                    >
                                        mail@laravelcs.com
                                    </button>
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-white bg-white border py-0 px-2 smaller fw-semibold text-secondary"
                                        @click="email = 'admin@example.com'; name = 'Admin User'"
                                    >
                                        admin@example.com
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label small fw-semibold text-secondary">
                                    আপনার নাম (ঐচ্ছিক)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-person text-muted"></i>
                                    </span>
                                    <input
                                        id="name"
                                        v-model="name"
                                        type="text"
                                        class="form-control border-start-0"
                                        :class="{ 'is-invalid': errors.name }"
                                        placeholder="উদাঃ তানভীর আহমেদ"
                                    />
                                </div>
                                <div v-if="errors.name" class="invalid-feedback-custom">
                                    <i class="bi bi-exclamation-circle"></i> {{ errors.name }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label small fw-semibold text-secondary">
                                    ইমেইল এড্রেস <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-envelope text-muted"></i>
                                    </span>
                                    <input
                                        id="email"
                                        v-model="email"
                                        type="email"
                                        class="form-control border-start-0"
                                        :class="{ 'is-invalid': errors.email }"
                                        placeholder="name@example.com"
                                        required
                                        autofocus
                                    />
                                </div>
                                <div v-if="errors.email" class="invalid-feedback-custom">
                                    <i class="bi bi-exclamation-circle"></i> {{ errors.email }}
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100 py-2 fw-semibold"
                                :disabled="isAuthenticating"
                            >
                                <span v-if="isAuthenticating" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <i v-else class="bi bi-send me-2"></i>
                                ওটিপি কোড পাঠান
                            </button>
                        </form>

                        <!-- Step 2: OTP Form -->
                        <form v-else @submit.prevent="handleVerifyOtp" novalidate>
                            <!-- Demo OTP Display Box -->
                            <div v-if="demoOtp" class="demo-otp-card p-3 rounded-3 mb-4 bg-primary-subtle border border-primary-subtle text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                    <span class="badge bg-primary text-white"><i class="bi bi-stars"></i> ডেমো মোড</span>
                                    <span class="small fw-bold text-primary">স্ক্রিনে ওটিপি কোড:</span>
                                </div>

                                <div class="d-flex align-items-center justify-content-center gap-2 my-2">
                                    <span class="fs-1 fw-bold font-monospace text-primary tracking-wide bg-white px-3 py-1 rounded-3 border shadow-sm">
                                        {{ demoOtp }}
                                    </span>
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-outline-primary"
                                        @click="copyDemoOtp"
                                        title="কোড কপি করুন"
                                    >
                                        <i :class="copied ? 'bi bi-check-lg text-success' : 'bi bi-clipboard'"></i>
                                        <span class="ms-1 small">{{ copied ? 'কপি হয়েছে' : 'কপি' }}</span>
                                    </button>
                                </div>

                                <div class="d-flex align-items-center justify-content-center gap-2 mt-2">
                                    <button 
                                        type="button" 
                                        class="btn btn-primary btn-sm px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                                        @click="autoFillOtp"
                                    >
                                        <i class="bi bi-arrow-down-circle"></i> কোডটি ইনপুটে বসান
                                    </button>
                                </div>

                                <div class="smaller text-muted mt-2 pt-2 border-top">
                                    <i class="bi bi-info-circle me-1"></i> লাইভ প্রোডাকশনে এই কোডটি স্ক্রিনে আসবে না, শুধুমাত্র আপনার আসল ইমেইল ইনবক্সে যাবে।
                                </div>
                            </div>

                            <!-- Live Production Helper Alert (When NOT in demo mode) -->
                            <div v-else class="alert alert-info py-2 px-3 small border-0 rounded-3 mb-3 text-start">
                                <i class="bi bi-envelope-open me-1"></i> আপনার <strong>{{ email }}</strong> ঠিকানায় ওটিপি পাঠানো হয়েছে। ইনবক্স ও স্প্যাম ফোল্ডার চেক করে নিচে কোডটি লিখুন।
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="otp" class="form-label small fw-semibold text-secondary mb-0">
                                        ৬-সংখ্যার কোড <span class="text-danger">*</span>
                                    </label>
                                    <button type="button" @click="backToStepOne" class="btn btn-link btn-sm p-0 text-decoration-none small">
                                        ইমেইল পরিবর্তন
                                    </button>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-key text-muted"></i>
                                    </span>
                                    <input
                                        id="otp"
                                        v-model="otp"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="6"
                                        class="form-control border-start-0 text-center fw-bold fs-4 letter-spacing-lg"
                                        :class="{ 'is-invalid': errors.otp }"
                                        placeholder="______"
                                        required
                                        autofocus
                                    />
                                </div>
                                <div v-if="errors.otp" class="invalid-feedback-custom">
                                    <i class="bi bi-exclamation-circle"></i> {{ errors.otp }}
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary w-100 py-2 fw-semibold mb-3"
                                :disabled="isAuthenticating || otp.length !== 6"
                            >
                                <span v-if="isAuthenticating" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <i v-else class="bi bi-check-lg me-2"></i>
                                কোড যাচাই করে প্রবেশ করুন
                            </button>

                            <div class="text-center pt-2 border-top">
                                <p class="small text-muted mb-2">কোড পাননি?</p>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm px-3"
                                    :disabled="countdown > 0 || isAuthenticating"
                                    @click="handleResend"
                                >
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    {{ countdown > 0 ? `পুনরায় পাঠান (${countdown}s)` : 'আবার ওটিপি পাঠান' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.letter-spacing-lg {
    letter-spacing: 0.35rem;
}
.tracking-wide {
    letter-spacing: 0.25rem;
}
.smaller {
    font-size: 0.78rem;
}
</style>

