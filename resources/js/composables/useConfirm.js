import { ref } from 'vue';

/**
 * Global confirmation dialog state (module-level singleton, same pattern as
 * useNotification / useTour). Rendered once in App.vue as <ConfirmDialog />.
 */
const isOpen = ref(false);
const options = ref({});

let resolvePromise = null;

const defaults = {
    title: 'নিশ্চিত করুন',
    message: '',
    confirmLabel: 'হ্যাঁ, চালিয়ে যান',
    cancelLabel: 'বাতিল',
    variant: 'danger', // 'danger' | 'primary'
    icon: null,
};

export function useConfirm() {
    /**
     * Open the confirmation dialog and resolve with true (confirmed) or false.
     * Accepts a plain message string or an options object.
     */
    const confirm = (config = {}) => {
        // Resolve any dialog that is still open so its promise never hangs.
        if (resolvePromise) {
            const resolvePrevious = resolvePromise;
            resolvePromise = null;
            resolvePrevious(false);
        }

        const resolved = typeof config === 'string' ? { message: config } : config;
        options.value = { ...defaults, ...resolved };
        isOpen.value = true;

        return new Promise((resolve) => {
            resolvePromise = resolve;
        });
    };

    const close = (result) => {
        isOpen.value = false;

        if (resolvePromise) {
            const resolve = resolvePromise;
            resolvePromise = null;
            resolve(result);
        }
    };

    return {
        isOpen,
        options,
        confirm,
        accept: () => close(true),
        cancel: () => close(false),
    };
}
