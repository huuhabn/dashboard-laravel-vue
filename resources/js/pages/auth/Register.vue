<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { http } from '@/api';
import TextLink from '@/components/common/TextLink.vue';
import InputError from '@/components/forms/InputError.vue';
import PasswordInput from '@/components/forms/PasswordInput.vue';
import PhoneInput from '@/components/forms/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import {
    completeOAuthTwoFactor,
    defaultPathAfterOAuthAuth,
    postSocialExchange,
    stripSocialExchangeQuery,
} from '@/composables/useOAuthSocialReturn';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { useAuthConfigStore } from '@/stores/authConfig';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const authConfigStore = useAuthConfigStore();

type Phase = 'form' | 'two-factor';

const phase = ref<Phase>('form');
const name = ref('');
const email = ref('');
const avatar = ref('');
const phoneE164 = ref('');
const defaultPhoneRegion = ref('VN');
const password = ref('');
const passwordConfirmation = ref('');
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const socialProviders = ref({ google: false, github: false });
const socialBanner = ref('');
const pendingToken = ref('');
const twoFactorCode = ref('');

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
    defaultPhoneRegion.value = authConfigStore.defaultPhoneRegion;
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
    await router.replace(defaultPathAfterOAuthAuth());
    processing.value = false;
}

onMounted(async () => {
    setSocialBannerFromQuery();
    await Promise.all([loadSocialProviders(), loadAuthConfig()]);

    const exchange = route.query.social_exchange;

    if (typeof exchange === 'string' && exchange.length > 0) {
        await redeemSocialExchange(exchange);
    }
});

function startSocial(provider: 'google' | 'github'): void {
    window.location.assign(`/auth/${provider}/redirect?intent=register`);
}

async function submit() {
    processing.value = true;
    errors.value = {};

    try {
        const { data } = await http.post('/auth/register', {
            name: name.value,
            email: email.value,
            avatar: avatar.value.trim() || undefined,
            phone: phoneE164.value.trim() || undefined,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
            device_name: 'spa',
        });

        if (typeof data.token !== 'string' || !data.token) {
            throw new Error('Invalid registration response: missing token.');
        }

        await auth.establishSession(data.token);
        await nextTick();
        await router.replace({ name: 'verify-email' });
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
    } finally {
        processing.value = false;
    }
}

async function submitTwoFactor() {
    processing.value = true;
    errors.value = {};
    const result = await completeOAuthTwoFactor(
        pendingToken.value,
        twoFactorCode.value,
    );

    if (!result.ok) {
        errors.value = result.errors ?? {};
        twoFactorCode.value = '';
        processing.value = false;

        return;
    }

    await auth.establishSession(result.token);
    await nextTick();
    await router.replace(defaultPathAfterOAuthAuth());
    processing.value = false;
}

function backToRegisterForm() {
    phase.value = 'form';
    pendingToken.value = '';
    twoFactorCode.value = '';
    errors.value = {};
}

const showSocial = computed(
    () => socialProviders.value.google || socialProviders.value.github,
);

const layoutTitle = computed(() =>
    phase.value === 'two-factor'
        ? t('auth.login.twoFactorTitle')
        : t('auth.register.title'),
);

const layoutDescription = computed(() =>
    phase.value === 'two-factor'
        ? t('auth.login.twoFactorDescription')
        : t('auth.register.description'),
);
</script>

<template>
    <AuthSplitLayout :title="layoutTitle" :description="layoutDescription">
        <div
            v-if="socialBanner"
            class="mb-4 rounded-md border border-destructive/50 bg-destructive/10 px-3 py-2 text-center text-sm text-destructive"
        >
            {{ socialBanner }}
        </div>

        <form
            v-if="phase === 'form'"
            class="flex flex-col gap-6"
            @submit.prevent="submit"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">{{ t('auth.register.name') }}</Label>
                    <Input
                        id="name"
                        v-model="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        :placeholder="t('auth.register.namePlaceholder')"
                    />
                    <InputError :message="errors.name?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.register.email') }}</Label>
                    <Input
                        id="email"
                        v-model="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        :placeholder="t('auth.register.emailPlaceholder')"
                    />
                    <InputError :message="errors.email?.[0]" />
                </div>

                <PhoneInput
                    id="reg-phone"
                    v-model="phoneE164"
                    :default-region="defaultPhoneRegion"
                    :label="t('auth.register.phone')"
                    :invalid="!!errors.phone?.[0]"
                />
                <InputError :message="errors.phone?.[0]" />

                <div class="grid gap-2">
                    <Label for="password">{{
                        t('auth.register.password')
                    }}</Label>
                    <PasswordInput
                        id="password"
                        v-model="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        :placeholder="t('auth.register.passwordPlaceholder')"
                    />
                    <InputError :message="errors.password?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{
                        t('auth.register.confirmPassword')
                    }}</Label>
                    <PasswordInput
                        id="password_confirmation"
                        v-model="passwordConfirmation"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        :placeholder="t('auth.register.confirmPlaceholder')"
                    />
                    <InputError :message="errors.password_confirmation?.[0]" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.register.submit') }}
                </Button>
            </div>

            <template v-if="showSocial">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <Separator />
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-background px-2 text-muted-foreground">
                            {{ t('auth.register.socialDivider') }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Button
                        v-if="socialProviders.google"
                        type="button"
                        variant="outline"
                        class="w-full"
                        :disabled="processing"
                        @click="startSocial('google')"
                    >
                        {{ t('auth.register.socialGoogle') }}
                    </Button>
                    <Button
                        v-if="socialProviders.github"
                        type="button"
                        variant="outline"
                        class="w-full"
                        :disabled="processing"
                        @click="startSocial('github')"
                    >
                        {{ t('auth.register.socialGithub') }}
                    </Button>
                </div>
            </template>

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth.register.hasAccount') }}
                <TextLink
                    :to="{ name: 'login' }"
                    class="underline underline-offset-4"
                    :tabindex="6"
                    >{{ t('auth.register.logIn') }}</TextLink
                >
            </div>
        </form>

        <form
            v-else
            class="flex flex-col gap-6"
            @submit.prevent="submitTwoFactor"
        >
            <div class="grid gap-2">
                <Label for="reg_two_factor_code">{{
                    t('auth.login.twoFactorCode')
                }}</Label>
                <Input
                    id="reg_two_factor_code"
                    v-model="twoFactorCode"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    maxlength="32"
                    :placeholder="t('auth.login.twoFactorCodePlaceholder')"
                />
                <InputError :message="errors.code?.[0]" />
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:justify-between">
                <Button
                    type="button"
                    variant="ghost"
                    class="sm:order-1"
                    :disabled="processing"
                    @click="backToRegisterForm"
                >
                    {{ t('auth.register.backFromTwoFactor') }}
                </Button>
                <Button type="submit" class="sm:order-2" :disabled="processing">
                    <Spinner v-if="processing" />
                    {{ t('auth.login.twoFactorSubmit') }}
                </Button>
            </div>
        </form>
    </AuthSplitLayout>
</template>
