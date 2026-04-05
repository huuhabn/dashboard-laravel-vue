import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { clearAccessToken, getAccessToken, http, setAccessToken } from '@/api';
import { unwrapApiData } from '@/lib/unwrap-api-data';
import type { User } from '@/types';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);

    /**
     * Mirror of the persisted Bearer token so `isAuthenticated` stays reactive.
     * Pinia getters (computed) cannot depend on localStorage alone — they would
     * cache the first read (e.g. false on /register).
     */
    const accessToken = ref<string | null>(null);

    const isAuthenticated = computed(() => !!accessToken.value);

    /** Call once on app boot so refresh with an existing token is recognized. */
    function hydrateFromStorage(): void {
        accessToken.value = getAccessToken();
    }

    /**
     * Persist token then load `/me` so `google_linked`, `has_password`, etc.
     * always match the API.
     */
    async function establishSession(token: string): Promise<void> {
        setAccessToken(token);
        accessToken.value = token;
        await fetchUser();
    }

    function clearSession(): void {
        clearAccessToken();
        accessToken.value = null;
        user.value = null;
    }

    async function fetchUser(): Promise<void> {
        const { data } = await http.get('/me');
        user.value = unwrapApiData<User>(data) ?? null;
    }

    async function logout(): Promise<void> {
        try {
            await http.delete('/auth/token');
        } finally {
            clearSession();
        }
    }

    return {
        user,
        accessToken,
        isAuthenticated,
        hydrateFromStorage,
        establishSession,
        clearSession,
        fetchUser,
        logout,
    };
});
