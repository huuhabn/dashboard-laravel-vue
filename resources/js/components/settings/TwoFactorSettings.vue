<script setup lang="ts">
import QRCode from 'qrcode';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { http } from '@/api';
import Heading from '@/components/common/Heading.vue';
import InputError from '@/components/forms/InputError.vue';
import PasswordInput from '@/components/forms/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAuthStore } from '@/stores/auth';

const props = defineProps<{
    twoFactorEnabled: boolean;
    hasPassword: boolean;
}>();

const { t } = useI18n();
const auth = useAuthStore();

const twoFactorQr = ref('');
const twoFactorSecret = ref('');
const setupCode = ref('');
const twoFactorErrors = ref<Record<string, string[]>>({});
const twoFactorProcessing = ref(false);
const twoFactorSetupPhase = ref(false);
const recoveryCodes = ref<string[] | null>(null);

const disableCode = ref('');
const disablePassword = ref('');
const disableErrors = ref<Record<string, string[]>>({});
const disableProcessing = ref(false);

watch(
    () => props.twoFactorEnabled,
    () => {
        if (props.twoFactorEnabled) {
            twoFactorSetupPhase.value = false;
            twoFactorQr.value = '';
            twoFactorSecret.value = '';
            setupCode.value = '';
        }
    },
);

async function startTwoFactorSetup() {
    twoFactorProcessing.value = true;
    twoFactorErrors.value = {};
    recoveryCodes.value = null;

    try {
        const { data } = await http.post<{
            data: { secret: string; otpauth_url: string };
        }>('/me/two-factor');
        twoFactorSecret.value = data.data.secret;
        twoFactorQr.value = await QRCode.toDataURL(data.data.otpauth_url, {
            width: 200,
            margin: 1,
        });
        twoFactorSetupPhase.value = true;
        setupCode.value = '';
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        if (err.response?.data?.errors) {
            twoFactorErrors.value = err.response.data.errors;
        }
    } finally {
        twoFactorProcessing.value = false;
    }
}

async function confirmTwoFactor() {
    twoFactorProcessing.value = true;
    twoFactorErrors.value = {};

    try {
        const { data } = await http.post<{
            data: { recovery_codes: string[] };
        }>('/me/two-factor/confirm', {
            code: setupCode.value.replace(/\s/g, ''),
        });
        recoveryCodes.value = data.data.recovery_codes;
        twoFactorSetupPhase.value = false;
        twoFactorQr.value = '';
        twoFactorSecret.value = '';
        setupCode.value = '';
        await auth.fetchUser();
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        if (err.response?.data?.errors) {
            twoFactorErrors.value = err.response.data.errors;
        }
    } finally {
        twoFactorProcessing.value = false;
    }
}

async function submitDisableTwoFactor() {
    disableProcessing.value = true;
    disableErrors.value = {};

    try {
        await http.delete('/me/two-factor', {
            data: {
                code: disableCode.value.replace(/\s/g, ''),
                current_password: disablePassword.value || undefined,
            },
        });
        disableCode.value = '';
        disablePassword.value = '';
        await auth.fetchUser();
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        if (err.response?.data?.errors) {
            disableErrors.value = err.response.data.errors;
        }
    } finally {
        disableProcessing.value = false;
    }
}

function cancelTwoFactorSetup() {
    twoFactorSetupPhase.value = false;
    twoFactorQr.value = '';
    twoFactorSecret.value = '';
    setupCode.value = '';
    twoFactorErrors.value = {};
}
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('security.twoFactorHeading')"
            :description="t('security.twoFactorDescription')"
        />

        <div
            v-if="recoveryCodes && recoveryCodes.length"
            class="rounded-lg border border-border bg-muted/40 p-4"
        >
            <p class="text-sm font-medium">
                {{ t('security.recoveryCodesTitle') }}
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ t('security.recoveryCodesHint') }}
            </p>
            <ul
                class="mt-3 grid grid-cols-1 gap-1 font-mono text-sm sm:grid-cols-2"
            >
                <li v-for="c in recoveryCodes" :key="c">{{ c }}</li>
            </ul>
            <Button
                type="button"
                variant="secondary"
                class="mt-4"
                @click="recoveryCodes = null"
            >
                {{ t('security.recoveryCodesDismiss') }}
            </Button>
        </div>

        <template v-if="!props.twoFactorEnabled && !twoFactorSetupPhase">
            <Button
                type="button"
                :disabled="twoFactorProcessing"
                @click="startTwoFactorSetup"
            >
                <Spinner v-if="twoFactorProcessing" />
                {{ t('security.enableTwoFactor') }}
            </Button>
            <InputError :message="twoFactorErrors.two_factor?.[0]" />
        </template>

        <div
            v-else-if="twoFactorSetupPhase"
            class="space-y-4 rounded-lg border border-border p-4"
        >
            <p class="text-sm text-muted-foreground">
                {{ t('security.twoFactorScanHint') }}
            </p>
            <div v-if="twoFactorQr" class="flex justify-center">
                <img
                    :src="twoFactorQr"
                    width="200"
                    height="200"
                    class="rounded-md border border-border"
                    alt=""
                />
            </div>
            <div class="space-y-1">
                <p class="text-xs text-muted-foreground">
                    {{ t('security.setupSecretHint') }}
                </p>
                <code
                    class="block rounded bg-muted px-2 py-1 text-sm break-all"
                    >{{ twoFactorSecret }}</code
                >
            </div>
            <div class="grid gap-2">
                <Label for="setup_totp">{{
                    t('security.totpCodeSetup')
                }}</Label>
                <Input
                    id="setup_totp"
                    v-model="setupCode"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="16"
                />
                <InputError :message="twoFactorErrors.code?.[0]" />
            </div>
            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    :disabled="twoFactorProcessing"
                    @click="confirmTwoFactor"
                >
                    <Spinner v-if="twoFactorProcessing" />
                    {{ t('security.confirmTwoFactor') }}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    :disabled="twoFactorProcessing"
                    @click="cancelTwoFactorSetup"
                >
                    {{ t('security.cancelSetup') }}
                </Button>
            </div>
        </div>

        <template v-else-if="props.twoFactorEnabled">
            <p class="text-sm text-muted-foreground">
                {{ t('security.twoFactorEnabled') }}
            </p>
            <form
                class="max-w-md space-y-4"
                @submit.prevent="submitDisableTwoFactor"
            >
                <div class="grid gap-2">
                    <Label for="disable_totp">{{
                        t('security.totpCodeDisable')
                    }}</Label>
                    <Input
                        id="disable_totp"
                        v-model="disableCode"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="32"
                        required
                    />
                    <InputError :message="disableErrors.code?.[0]" />
                </div>
                <div v-if="props.hasPassword" class="grid gap-2">
                    <Label for="disable_pw">{{
                        t('security.currentPasswordForDisable')
                    }}</Label>
                    <PasswordInput
                        id="disable_pw"
                        v-model="disablePassword"
                        autocomplete="current-password"
                        required
                    />
                    <InputError
                        :message="disableErrors.current_password?.[0]"
                    />
                </div>
                <Button
                    type="submit"
                    variant="destructive"
                    :disabled="disableProcessing"
                >
                    <Spinner v-if="disableProcessing" />
                    {{ t('security.disableTwoFactor') }}
                </Button>
            </form>
        </template>
    </div>
</template>
