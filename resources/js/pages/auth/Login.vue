<script setup lang="ts">
import { CircleCheck } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import { http } from '@/api';
import OtpLoginForm from '@/components/auth/OtpLoginForm.vue';
import OtpVerifyForm from '@/components/auth/OtpVerifyForm.vue';
import PasswordLoginForm from '@/components/auth/PasswordLoginForm.vue';
import TwoFactorChallengeForm from '@/components/auth/TwoFactorChallengeForm.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    completeOAuthTwoFactor,
    defaultPathAfterOAuthAuth,
    postSocialExchange,
    stripSocialExchangeQuery,
} from '@/composables/useOAuthSocialReturn';
import { dashboardAppPath } from '@/config/dashboardPrefix';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { safeAuthRedirectPath } from '@/lib/safe-redirect';
import { useAuthStore } from '@/stores/auth';
import { useAuthConfigStore } from '@/stores/authConfig';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const authConfigStore = useAuthConfigStore();

type LoginMethod = 'password' | 'otp';
type Phase = 'form' | 'otp-code' | 'two-factor';

const loginMethod = ref<LoginMethod>('password');
const phase = ref<Phase>('form');
const pendingToken = ref('');
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const socialProviders = ref({ google: false, github: false });
const socialBanner = ref('');
const otpInfoBanner = ref('');

// To retain the state of user inputs from the child components so we can request an OTP again
const currentOtpIdentifier = ref<{ email?: string; phone?: string }>({});

/** Epoch ms when the user may request another OTP (from API). */
const otpResendAvailableAtMs = ref<number | null>(null);
const otpResendNowTick = ref(Date.now());
let otpResendTickTimer: ReturnType<typeof setInterval> | null = null;

const passwordFormRef = ref<InstanceType<typeof PasswordLoginForm> | null>(
    null,
);
const otpVerifyRef = ref<InstanceType<typeof OtpVerifyForm> | null>(null);
const twoFactorRef = ref<InstanceType<typeof TwoFactorChallengeForm> | null>(
    null,
);

const resolvedLoginMethods = computed(() => {
    const m = authConfigStore.loginMethods;

    if (m === 'password_only' || m === 'otp_only' || m === 'both') {
        return m;
    }

    return 'both';
});

const showPasswordLogin = computed(() => {
    const m = resolvedLoginMethods.value;

    return m === 'password_only' || m === 'both';
});

const showOtpLogin = computed(() => {
    const m = resolvedLoginMethods.value;

    return m === 'otp_only' || m === 'both';
});

const showMethodTabs = computed(
    () => showPasswordLogin.value && showOtpLogin.value,
);

const otpResendSecondsLeft = computed(() => {
    if (otpResendAvailableAtMs.value == null) {
        return 0;
    }

    return Math.max(
        0,
        Math.ceil(
            (otpResendAvailableAtMs.value - otpResendNowTick.value) / 1000,
        ),
    );
});

const otpDeliverTo = computed(
    () => authConfigStore.otpDeliverTo as 'email' | 'phone' | 'both',
);
const defaultPhoneRegion = computed(() => authConfigStore.defaultPhoneRegion);

const status = computed(() =>
    typeof route.query.verified === 'string'
        ? t('auth.login.verifiedBanner')
        : '',
);

const layoutTitle = computed(() => {
    if (phase.value === 'two-factor') {
        return t('auth.login.twoFactorTitle');
    }

    if (phase.value === 'otp-code') {
        return t('auth.login.otpVerifyTitle');
    }

    if (loginMethod.value === 'otp') {
        return t('auth.login.otpFormTitle');
    }

    return t('auth.login.title');
});

const layoutDescription = computed(() => {
    if (phase.value === 'two-factor') {
        return t('auth.login.twoFactorDescription');
    }

    if (phase.value === 'otp-code') {
        return t('auth.login.otpSentDescription');
    }

    if (loginMethod.value === 'otp') {
        return t('auth.login.otpFormDescription');
    }

    return t('auth.login.description');
});

function setSocialBannerFromQuery(): void {
    const err = route.query.social_error;

    if (err === '1') {
        socialBanner.value = t('auth.login.socialError');
    } else if (err === '2') {
        socialBanner.value = t('auth.login.socialNoEmail');
    } else {
        socialBanner.value = '';
    }
}

async function loadSocialProviders(): Promise<void> {
    socialProviders.value = await authConfigStore.ensureSocialProviders();
}

async function loadAuthConfig(): Promise<void> {
    await authConfigStore.ensureConfig();
    const lm = authConfigStore.loginMethods;

    if (lm === 'otp_only') {
        loginMethod.value = 'otp';
    } else if (lm === 'password_only') {
        loginMethod.value = 'password';
    }
}

async function redeemSocialExchange(token: string): Promise<void> {
    processing.value = true;
    errors.value = {};
    const result = await postSocialExchange(token);

    if (!result.ok) {
        errors.value = result.errors ?? {};
        await stripSocialExchangeQuery(router, route);
        processing.value = false;

        return;
    }

    if (result.data.kind === 'two_factor') {
        pendingToken.value = result.data.pending_token;
        phase.value = 'two-factor';
        await stripSocialExchangeQuery(router, route);
        processing.value = false;

        return;
    }

    await auth.establishSession(result.data.token);
    await nextTick();
    const redirect = safeAuthRedirectPath(
        route.query.redirect,
        defaultPathAfterOAuthAuth(),
    );
    await router.replace(redirect);
    processing.value = false;
}

onMounted(async () => {
    setSocialBannerFromQuery();
    await Promise.all([loadSocialProviders(), loadAuthConfig()]);

    const exchange = route.query.social_exchange;

    if (typeof exchange === 'string' && exchange.length > 0) {
        await redeemSocialExchange(exchange);
    }

    otpResendTickTimer = setInterval(() => {
        otpResendNowTick.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (otpResendTickTimer !== null) {
        clearInterval(otpResendTickTimer);
        otpResendTickTimer = null;
    }
});

function startSocial(provider: 'google' | 'github'): void {
    window.location.assign(`/auth/${provider}/redirect`);
}

function setLoginMethod(method: LoginMethod): void {
    if (method === 'password' && !showPasswordLogin.value) {
        return;
    }

    if (method === 'otp' && !showOtpLogin.value) {
        return;
    }

    loginMethod.value = method;
    phase.value = 'form';
    errors.value = {};
    otpInfoBanner.value = '';
    otpResendAvailableAtMs.value = null;
}

async function submitCredentials(credentials: {
    email?: string;
    password?: string;
}) {
    processing.value = true;
    errors.value = {};

    try {
        const { data } = await http.post<{
            token?: string;
            two_factor_required?: boolean;
            pending_token?: string;
            user?: unknown;
        }>('/auth/token', {
            ...credentials,
            device_name: 'spa',
        });

        if (data.two_factor_required && data.pending_token) {
            pendingToken.value = data.pending_token;
            phase.value = 'two-factor';
            passwordFormRef.value?.clearPassword();

            return;
        }

        if (typeof data.token !== 'string' || !data.token) {
            throw new Error('Invalid login response: missing token.');
        }

        await auth.establishSession(data.token);
        await nextTick();
        const redirect = safeAuthRedirectPath(
            route.query.redirect,
            dashboardAppPath('dashboard'),
        );
        await router.replace(redirect);
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }

        passwordFormRef.value?.clearPassword();
    } finally {
        processing.value = false;
    }
}

async function requestOtp(identifier?: { email?: string; phone?: string }) {
    if (identifier) {
        currentOtpIdentifier.value = identifier;
    }

    const payload = currentOtpIdentifier.value;

    processing.value = true;
    errors.value = {};
    otpInfoBanner.value = '';

    if (Object.keys(payload).length === 0) {
        // Validation missing
        const d = otpDeliverTo.value;

        if (d === 'phone') {
            errors.value = { phone: [t('auth.login.otpPhoneRequired')] };
        } else {
            errors.value = { email: [t('auth.login.otpEmailRequired')] };
        }

        processing.value = false;

        return;
    }

    try {
        const { data } = await http.post<{
            message?: string;
            resend_available_at?: string | null;
        }>('/auth/otp/request', payload);

        const sentMessage = data.message || t('auth.login.otpGenericSent');
        otpInfoBanner.value = sentMessage;
        phase.value = 'otp-code';
        otpVerifyRef.value?.clearCode();
        otpResendNowTick.value = Date.now();
        toast.success(t('auth.login.otpSentHeadline'), {
            description: sentMessage,
        });

        if (typeof data.resend_available_at === 'string') {
            const ts = Date.parse(data.resend_available_at);
            otpResendAvailableAtMs.value = Number.isNaN(ts) ? null : ts;
        } else {
            otpResendAvailableAtMs.value = null;
        }
    } catch (e: unknown) {
        const err = e as {
            response?: {
                data?: {
                    errors?: Record<string, string[]>;
                    retry_after_seconds?: number;
                };
            };
        };
        const resData = err.response?.data;

        if (resData?.errors) {
            errors.value = resData.errors;
        }

        if (typeof resData?.retry_after_seconds === 'number') {
            otpResendNowTick.value = Date.now();
            otpResendAvailableAtMs.value =
                Date.now() + resData.retry_after_seconds * 1000;
        }
    } finally {
        processing.value = false;
    }
}

async function verifyOtp(code: string) {
    processing.value = true;
    errors.value = {};

    try {
        const { data } = await http.post<{
            token?: string;
            two_factor_required?: boolean;
            pending_token?: string;
            user?: unknown;
        }>('/auth/otp/verify', {
            ...currentOtpIdentifier.value,
            code: code.replace(/\s/g, ''),
            device_name: 'spa',
        });

        if (data.two_factor_required && data.pending_token) {
            pendingToken.value = data.pending_token;
            phase.value = 'two-factor';
            otpVerifyRef.value?.clearCode();

            return;
        }

        if (typeof data.token !== 'string' || !data.token) {
            throw new Error('Invalid OTP verify response: missing token.');
        }

        await auth.establishSession(data.token);
        await nextTick();
        const redirect = safeAuthRedirectPath(
            route.query.redirect,
            dashboardAppPath('dashboard'),
        );
        await router.replace(redirect);
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }

        otpVerifyRef.value?.clearCode();
    } finally {
        processing.value = false;
    }
}

async function submitTwoFactor(code: string) {
    processing.value = true;
    errors.value = {};
    const result = await completeOAuthTwoFactor(pendingToken.value, code);

    if (!result.ok) {
        errors.value = result.errors ?? {};
        twoFactorRef.value?.clearCode();
        processing.value = false;

        return;
    }

    await auth.establishSession(result.token);
    await nextTick();
    const redirect = safeAuthRedirectPath(
        route.query.redirect,
        defaultPathAfterOAuthAuth(),
    );
    await router.replace(redirect);
    processing.value = false;
}

function backToLoginForm() {
    phase.value = 'form';
    pendingToken.value = '';
    errors.value = {};
}

function backFromOtpCode() {
    phase.value = 'form';
    otpInfoBanner.value = '';
    otpResendAvailableAtMs.value = null;
    errors.value = {};
}

const showSocial = computed(
    () =>
        showPasswordLogin.value &&
        (socialProviders.value.google || socialProviders.value.github) &&
        phase.value === 'form' &&
        loginMethod.value === 'password',
);
</script>

<template>
    <AuthSplitLayout :title="layoutTitle" :description="layoutDescription">
        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <div
            v-if="socialBanner"
            class="mb-4 rounded-md border border-destructive/50 bg-destructive/10 px-3 py-2 text-center text-sm text-destructive"
        >
            {{ socialBanner }}
        </div>

        <Alert
            v-if="otpInfoBanner && phase === 'otp-code'"
            class="mb-4 border-primary/35 bg-primary/5 text-foreground"
            role="status"
        >
            <CircleCheck class="text-primary" />
            <AlertTitle>{{ t('auth.login.otpSentHeadline') }}</AlertTitle>
            <AlertDescription class="text-muted-foreground">
                {{ otpInfoBanner }}
            </AlertDescription>
        </Alert>

        <div
            v-if="phase !== 'two-factor' && showMethodTabs"
            class="mb-6 flex rounded-lg border border-border p-1"
            role="tablist"
        >
            <Button
                type="button"
                variant="ghost"
                class="h-9 flex-1"
                :class="
                    loginMethod === 'password'
                        ? 'bg-background shadow-sm'
                        : 'text-muted-foreground'
                "
                @click="setLoginMethod('password')"
            >
                {{ t('auth.login.loginWithPassword') }}
            </Button>
            <Button
                type="button"
                variant="ghost"
                class="h-9 flex-1"
                :class="
                    loginMethod === 'otp'
                        ? 'bg-background shadow-sm'
                        : 'text-muted-foreground'
                "
                @click="setLoginMethod('otp')"
            >
                {{ t('auth.login.loginWithOtp') }}
            </Button>
        </div>

        <PasswordLoginForm
            v-if="
                phase === 'form' &&
                loginMethod === 'password' &&
                showPasswordLogin
            "
            ref="passwordFormRef"
            :processing="processing"
            :errors="errors"
            :show-social="showSocial"
            :social-providers="socialProviders"
            @submit="submitCredentials"
            @social="startSocial"
        />

        <OtpLoginForm
            v-else-if="
                phase === 'form' && loginMethod === 'otp' && showOtpLogin
            "
            :processing="processing"
            :errors="errors"
            :otp-deliver-to="otpDeliverTo"
            :default-phone-region="defaultPhoneRegion"
            @request="requestOtp"
        />

        <OtpVerifyForm
            v-else-if="phase === 'otp-code'"
            ref="otpVerifyRef"
            :processing="processing"
            :errors="errors"
            :resend-seconds-left="otpResendSecondsLeft"
            @submit="verifyOtp"
            @cancel="backFromOtpCode"
            @resend="() => requestOtp()"
        />

        <TwoFactorChallengeForm
            v-else
            ref="twoFactorRef"
            :processing="processing"
            :errors="errors"
            @submit="submitTwoFactor"
            @cancel="backToLoginForm"
        />
    </AuthSplitLayout>
</template>
