<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

const { isOpen, options, accept, cancel } = useConfirm();

const cancelButton = ref(null);

const isDanger = computed(() => options.value.variant === 'danger');

const iconClass = computed(() => {
    if (options.value.icon) {
        return options.value.icon;
    }

    return isDanger.value ? 'bi-exclamation-triangle-fill' : 'bi-question-circle-fill';
});

const handleKeydown = (event) => {
    if (!isOpen.value) return;

    if (event.key === 'Escape') {
        cancel();
    } else if (event.key === 'Enter') {
        accept();
    }
};

watch(isOpen, async (open) => {
    document.body.style.overflow = open ? 'hidden' : '';

    if (open) {
        window.addEventListener('keydown', handleKeydown);
        await nextTick();
        cancelButton.value?.focus();
    } else {
        window.removeEventListener('keydown', handleKeydown);
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <div v-if="isOpen" class="confirm-backdrop" @click.self="cancel">
        <div class="confirm-card card" role="alertdialog" aria-modal="true" :aria-label="options.title">
            <div class="d-flex align-items-start gap-3 p-4 pb-3">
                <span class="confirm-icon" :class="{ 'confirm-icon-danger': isDanger }">
                    <i class="bi" :class="iconClass"></i>
                </span>

                <div class="flex-grow-1">
                    <h5 class="confirm-title fw-bold mb-2">{{ options.title }}</h5>
                    <p v-if="options.message" class="confirm-message text-secondary small mb-0">
                        {{ options.message }}
                    </p>
                </div>

                <button type="button" class="btn-close" aria-label="বন্ধ করুন" @click="cancel"></button>
            </div>

            <div class="confirm-footer d-flex flex-wrap justify-content-end gap-2 px-4 py-3">
                <button ref="cancelButton" type="button" class="btn btn-outline-secondary px-3" @click="cancel">
                    {{ options.cancelLabel }}
                </button>
                <button
                    type="button"
                    class="btn px-3"
                    :class="isDanger ? 'btn-danger' : 'btn-primary'"
                    @click="accept"
                >
                    {{ options.confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.confirm-backdrop {
    position: fixed;
    inset: 0;
    z-index: 100002;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(28, 33, 30, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    animation: confirm-fade 0.18s ease-out;
}

.confirm-card {
    width: 100%;
    max-width: 460px;
    border-radius: var(--radius-card);
    box-shadow: 0 24px 48px -20px rgba(28, 33, 30, 0.35);
    animation: confirm-pop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.confirm-icon {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 1.2rem;
    background: var(--brand-soft);
    color: var(--brand-primary);
}

.confirm-icon-danger {
    background: #fef2f2;
    color: #dc2626;
}

.confirm-title {
    color: var(--text-primary);
    letter-spacing: -0.02em;
}

.confirm-message {
    line-height: 1.7;
}

.confirm-footer {
    border-top: 1px solid var(--border-default);
    background: var(--surface-card);
    border-bottom-left-radius: var(--radius-card);
    border-bottom-right-radius: var(--radius-card);
}

@keyframes confirm-fade {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes confirm-pop {
    from {
        opacity: 0;
        transform: translateY(14px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 575.98px) {
    .confirm-footer {
        justify-content: center !important;
    }

    .confirm-footer .btn {
        flex: 1 1 auto;
    }
}
</style>
