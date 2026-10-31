import { ref, computed } from 'vue';
import api from '../services/api';

const user = ref(JSON.parse(localStorage.getItem('amar_auth_user') || 'null'));
const token = ref(localStorage.getItem('amar_auth_token') || null);
const isAuthenticating = ref(false);

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value && !!user.value);
    const isAdmin = computed(() => user.value?.role === 'admin');

    const setAuth = (userData, tokenValue) => {
        user.value = userData;
        token.value = tokenValue;
        localStorage.setItem('amar_auth_user', JSON.stringify(userData));
        localStorage.setItem('amar_auth_token', tokenValue);
    };

    const clearAuth = () => {
        user.value = null;
        token.value = null;
        localStorage.removeItem('amar_auth_user');
        localStorage.removeItem('amar_auth_token');
    };

    const sendOtp = async (email, name = null) => {
        isAuthenticating.value = true;
        try {
            const response = await api.post('/auth/send-otp', { email, name });
            return response.data;
        } finally {
            isAuthenticating.value = false;
        }
    };

    const verifyOtp = async (email, otp, name = null) => {
        isAuthenticating.value = true;
        try {
            const response = await api.post('/auth/verify-otp', { email, otp, name });
            if (response.data.success) {
                setAuth(response.data.user, response.data.token);
            }
            return response.data;
        } finally {
            isAuthenticating.value = false;
        }
    };

    const fetchCurrentUser = async () => {
        if (!token.value) return null;
        try {
            const response = await api.get('/auth/me');
            if (response.data.success) {
                user.value = response.data.user;
                localStorage.setItem('amar_auth_user', JSON.stringify(response.data.user));
            }
            return user.value;
        } catch (err) {
            clearAuth();
            return null;
        }
    };

    const logout = async () => {
        try {
            await api.post('/auth/logout');
        } catch (e) {
            // Ignore error on logout
        } finally {
            clearAuth();
        }
    };

    return {
        user,
        token,
        isAuthenticated,
        isAdmin,
        isAuthenticating,
        sendOtp,
        verifyOtp,
        fetchCurrentUser,
        logout,
    };
}

