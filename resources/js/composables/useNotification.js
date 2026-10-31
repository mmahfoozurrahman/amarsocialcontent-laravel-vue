import { ref } from 'vue';

const notification = ref(null);
let timeoutId = null;

export function useNotification() {
    const notify = (message, type = 'info', duration = 4000) => {
        if (timeoutId) clearTimeout(timeoutId);
        let text = message;
        if (typeof message === 'object' && message !== null) {
            text = message.message || message.error || JSON.stringify(message);
        }
        if (!text || text === 'undefined') {
            text = type === 'danger'
                ? 'একটি সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।'
                : 'অপারেশন সম্পন্ন হয়েছে।';
        }
        notification.value = { message: String(text), type };
        timeoutId = setTimeout(() => {
            notification.value = null;
        }, duration);
    };

    const clear = () => {
        if (timeoutId) clearTimeout(timeoutId);
        notification.value = null;
    };

    return {
        notification,
        notify,
        clear,
        success: (msg) => notify(msg, 'success'),
        error: (msg) => notify(msg, 'danger', 6000),
        info: (msg) => notify(msg, 'info'),
        warning: (msg) => notify(msg, 'warning'),
    };
}

