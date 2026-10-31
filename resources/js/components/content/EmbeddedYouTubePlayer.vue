<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    videoId: {
        type: String,
        required: true,
    },
    contentType: {
        type: String,
        default: 'video', // 'video' | 'short'
    },
    title: {
        type: String,
        default: 'YouTube Video',
    },
});

const isCollapsed = ref(false);

const embedUrl = computed(() => {
    return `https://www.youtube-nocookie.com/embed/${props.videoId}?rel=0&modestbranding=1&enablejsapi=1`;
});

const isShort = computed(() => props.contentType === 'short');
</script>

<template>
    <div class="embedded-player-card card border-0 shadow-sm overflow-hidden mb-4 bg-white">
        <!-- Player Header / Controls Bar -->
        <div class="card-header bg-light border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="bg-danger text-white rounded px-1 py-0.5 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                    <i class="bi bi-youtube small"></i>
                </span>
                <span class="fw-semibold small text-dark text-truncate" style="max-width: 320px;">
                    {{ title }}
                </span>
                <span v-if="isShort" class="badge bg-danger-subtle text-danger ms-1">
                    <i class="bi bi-lightning-fill"></i> Shorts
                </span>
            </div>

            <div class="d-flex align-items-center gap-1">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary py-0 px-2"
                    :title="isCollapsed ? 'ভিডিও প্রদর্শন করুন' : 'ভিডিও সংকুচিত করুন'"
                    @click="isCollapsed = !isCollapsed"
                >
                    <i class="bi" :class="isCollapsed ? 'bi-chevron-down' : 'bi-chevron-up'"></i>
                    <span class="smaller ms-1">{{ isCollapsed ? 'প্লেয়ার খুলুন' : 'লুকান' }}</span>
                </button>
            </div>
        </div>

        <!-- Video Player Body -->
        <div v-show="!isCollapsed" class="player-wrapper text-center bg-black position-relative p-2">
            <div
                class="video-container mx-auto rounded overflow-hidden"
                :class="isShort ? 'shorts-ratio' : 'standard-ratio'"
            >
                <iframe
                    :src="embedUrl"
                    class="w-100 h-100 border-0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
        </div>
    </div>
</template>

<style scoped>
.smaller {
    font-size: 0.75rem;
}

.standard-ratio {
    width: 100%;
    aspect-ratio: 16 / 9;
    max-height: 480px;
}

.shorts-ratio {
    width: 100%;
    max-width: 340px;
    aspect-ratio: 9 / 16;
    max-height: 540px;
}
</style>

