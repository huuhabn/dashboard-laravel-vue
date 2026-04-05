import { defineStore } from 'pinia';
import { computed, ref, shallowRef } from 'vue';
import { http } from '@/api';
import { unwrapApiData } from '@/lib/unwrap-api-data';

export interface AuthSaasConfig {
    login_methods: string;
    otp_deliver_to: string;
    default_phone_region: string;
}

export interface SocialProviders {
    google: boolean;
    github: boolean;
}

export const useAuthConfigStore = defineStore('authConfig', () => {
    const config = ref<AuthSaasConfig | null>(null);
    const socialProviders = ref<SocialProviders | null>(null);

    const _configPromise = shallowRef<Promise<void> | null>(null);
    const _socialPromise = shallowRef<Promise<void> | null>(null);

    const loginMethods = computed(() => config.value?.login_methods ?? 'both');
    const otpDeliverTo = computed(
        () => config.value?.otp_deliver_to ?? 'email',
    );
    const defaultPhoneRegion = computed(
        () => config.value?.default_phone_region ?? 'VN',
    );

    /**
     * Fetch auth config once; subsequent calls return the cached promise
     * so multiple components mounting in parallel don't fire duplicate requests.
     */
    async function ensureConfig(): Promise<AuthSaasConfig | null> {
        if (config.value) {
            return config.value;
        }

        if (!_configPromise.value) {
            _configPromise.value = (async () => {
                try {
                    const { data } = await http.get<unknown>('/auth/config');
                    config.value = unwrapApiData<AuthSaasConfig>(data) ?? null;
                } catch {
                    config.value = null;
                }
            })();
        }

        await _configPromise.value;

        return config.value;
    }

    /**
     * Fetch social providers once; cached like ensureConfig.
     */
    async function ensureSocialProviders(): Promise<SocialProviders> {
        const fallback: SocialProviders = { google: false, github: false };

        if (socialProviders.value) {
            return socialProviders.value;
        }

        if (!_socialPromise.value) {
            _socialPromise.value = (async () => {
                try {
                    const { data } = await http.get<unknown>(
                        '/auth/social/providers',
                    );
                    socialProviders.value =
                        unwrapApiData<SocialProviders>(data) ?? fallback;
                } catch {
                    socialProviders.value = fallback;
                }
            })();
        }

        await _socialPromise.value;

        return socialProviders.value ?? fallback;
    }

    return {
        config,
        socialProviders,
        loginMethods,
        otpDeliverTo,
        defaultPhoneRegion,
        ensureConfig,
        ensureSocialProviders,
    };
});
