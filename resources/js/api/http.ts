import axios from 'axios';
import type { AxiosError } from 'axios';
import { API_V1_BASE_URL } from '@/api/config';
import { clearAccessToken, getAccessToken } from '@/api/token';
import { buildAcceptLanguageHeader } from '@/i18n/locale-storage';

/**
 * Axios client for `/api/v1`. Attaches `Authorization: Bearer` from token storage.
 */
export const http = axios.create({
    baseURL: API_V1_BASE_URL,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

http.interceptors.request.use((config) => {
    const token = getAccessToken();

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    if (config.data instanceof FormData) {
        delete config.headers['Content-Type'];
    }

    config.headers['Accept-Language'] = buildAcceptLanguageHeader();

    return config;
});

const GUEST_PATH_PREFIXES = [
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password',
];

function shouldRedirectToLoginOn401(): boolean {
    const path = window.location.pathname;

    return !GUEST_PATH_PREFIXES.some(
        (prefix) => path === prefix || path.startsWith(`${prefix}/`),
    );
}

http.interceptors.response.use(
    (response) => response,
    async (error: AxiosError) => {
        const status = error.response?.status;

        if (status === 401) {
            clearAccessToken();

            try {
                const { useAuthStore } = await import('@/stores/auth');
                useAuthStore().$patch({ user: null, accessToken: null });
            } catch {
                /* Pinia may be unavailable during bootstrap */
            }

            if (shouldRedirectToLoginOn401()) {
                window.location.assign('/login');
            }
        }

        return Promise.reject(error);
    },
);
