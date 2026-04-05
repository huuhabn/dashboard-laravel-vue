<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/forms/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    processing: boolean;
    errors: Record<string, string[]>;
    resendSecondsLeft: number;
}>();

const emit = defineEmits<{
    submit: [code: string];
    cancel: [];
    resend: [];
}>();

const { t } = useI18n();
const code = ref('');

function onSubmit() {
    emit('submit', code.value);
}

defineExpose({
    clearCode: () => {
        code.value = '';
    },
});
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
        <div class="grid gap-2">
            <Label for="otp_code">{{ t('auth.login.otpCodeLabel') }}</Label>
            <InputOTP
                id="otp_code"
                v-model="code"
                :maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                :placeholder="t('auth.login.otpCodePlaceholder')"
            >
                <InputOTPGroup>
                    <InputOTPSlot v-for="i in 6" :key="i" :index="i - 1" />
                </InputOTPGroup>
            </InputOTP>
            <InputError :message="props.errors.code?.[0]" />
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:justify-between">
            <Button
                type="button"
                variant="ghost"
                class="sm:order-1"
                :disabled="props.processing"
                @click="emit('cancel')"
            >
                {{ t('auth.login.otpChangeIdentifier') }}
            </Button>
            <Button
                type="submit"
                class="sm:order-2"
                :disabled="props.processing"
            >
                <Spinner v-if="props.processing" />
                {{ t('auth.login.otpVerify') }}
            </Button>
        </div>

        <Button
            type="button"
            variant="outline"
            class="w-full"
            :disabled="props.processing || props.resendSecondsLeft > 0"
            @click="emit('resend')"
        >
            {{
                props.resendSecondsLeft > 0
                    ? t('auth.login.otpResendWait', {
                          seconds: props.resendSecondsLeft,
                      })
                    : t('auth.login.otpResend')
            }}
        </Button>
    </form>
</template>
