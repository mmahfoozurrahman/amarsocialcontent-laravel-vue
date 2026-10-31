import { ref } from 'vue';

const isLoading = ref(false);
let finishTimeout = null;

export function usePreloader() {
    const start = () => {
        if (finishTimeout) clearTimeout(finishTimeout);
        isLoading.value = true;
    };

    const finish = (minDuration = 180) => {
        if (finishTimeout) clearTimeout(finishTimeout);
        finishTimeout = setTimeout(() => {
            isLoading.value = false;
        }, minDuration);
    };

    return {
        isLoading,
        start,
        finish,
    };
}
