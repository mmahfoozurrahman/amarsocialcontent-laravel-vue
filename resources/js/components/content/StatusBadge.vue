<script setup>
defineProps({
    status: {
        type: String,
        required: true,
    },
});

const statusMap = {
    pending: { label: 'অপেক্ষমাণ', class: 'bg-secondary-subtle text-secondary', icon: 'bi-hourglass' },
    extracting_transcript: { label: 'ক্যাপশন সংগ্রহ হচ্ছে...', class: 'bg-info-subtle text-info', icon: 'bi-arrow-repeat spin' },
    processing_transcript: { label: 'বিশ্লেষণ চলছে...', class: 'bg-primary-subtle text-primary', icon: 'bi-cpu spin' },
    generating_bengali: { label: 'বাংলা তৈরি হচ্ছে...', class: 'bg-warning-subtle text-warning', icon: 'bi-stars spin' },
    completed: { label: 'সম্পন্ন', class: 'bg-success-subtle text-success', icon: 'bi-check-circle-fill' },
    failed: { label: 'ব্যর্থ হয়েছে', class: 'bg-danger-subtle text-danger', icon: 'bi-exclamation-triangle-fill' },
};
</script>

<template>
    <span class="badge d-inline-flex align-items-center gap-1 px-2 py-1" :class="statusMap[status]?.class || 'bg-light text-dark'">
        <i class="bi" :class="statusMap[status]?.icon || 'bi-circle'"></i>
        <span>{{ statusMap[status]?.label || status }}</span>
    </span>
</template>

<style scoped>
.spin {
    animation: badge-spin 1.5s linear infinite;
}
@keyframes badge-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

