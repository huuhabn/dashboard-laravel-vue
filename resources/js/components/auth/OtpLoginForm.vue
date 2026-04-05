<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import TextLink from '@/components/common/TextLink.vue';
import InputError from '@/components/forms/InputError.vue';
import PhoneInput from '@/components/forms/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    processing: boolean;
    errors: Record<string, string[]>;
    otpDeliverTo: 'email' | 'phone' | 'both';
    defaultPhoneRegion: string;
}>();

const emit = defineEmits<{
    request: [identifier: { email?: string; phone?: string }];
}>();

const { t } = useI18n();

const otpTo = ref<'email' | 'phone'>('email');
const email = ref('');
const phoneE164 = ref('');

function onSubmit() {
    const d = props.otpDeliverTo;
    const needsPhone =
        d === 'phone' || (d === 'both' && otpTo.value === 'phone');
    const needsEmail =
        d === 'email' || (d === 'both' && otpTo.value === 'email');

    // The parent has identical validation handling, but we can pass the raw data up
    if (needsPhone && !phoneE164.value.trim()) {
        emit('request', {}); // Will trigger validation in parent

        return;
    }

    if (needsEmail && !email.value.trim()) {
        emit('request', {}); // Will trigger validation in parent

        return;
    }

    if (needsEmail) {
        emit('request', { email: email.value });
    } else {
        emit('request', { phone: phoneE164.value });
    }
}
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
        <div class="grid gap-6">
            <div
                v-if="props.otpDeliverTo === 'both'"
                class="flex rounded-lg border border-border p-1"
                role="tablist"
            >
                <Button
                    type="button"
                    variant="ghost"
                    class="h-9 flex-1"
                    :class="
                        otpTo === 'email'
                            ? 'bg-background shadow-sm'
                            : 'text-muted-foreground'
                    "
                    @click="otpTo = 'email'"
                >
                    {{ t('auth.login.otpChannelEmail') }}
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    class="h-9 flex-1"
                    :class="
                        otpTo === 'phone'
                            ? 'bg-background shadow-sm'
                            : 'text-muted-foreground'
                    "
                    @click="otpTo = 'phone'"
                >
                    {{ t('auth.login.otpChannelPhone') }}
                </Button>
            </div>

            <div
                v-if="
                    props.otpDeliverTo === 'email' ||
                    (props.otpDeliverTo === 'both' && otpTo === 'email')
                "
                class="grid gap-2"
            >
                <Label for="email-otp">{{ t('auth.login.email') }}</Label>
                <Input
                    id="email-otp"
                    v-model="email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    :placeholder="t('auth.login.emailPlaceholder')"
                />
                <InputError :message="props.errors.email?.[0]" />
            </div>

            <div
                v-if="
                    props.otpDeliverTo === 'phone' ||
                    (props.otpDeliverTo === 'both' && otpTo === 'phone')
                "
                class="grid gap-2"
            >
                <PhoneInput
                    id="login-phone-otp"
                    v-model="phoneE164"
                    :default-region="props.defaultPhoneRegion"
                    :label="t('auth.login.phone')"
                    :invalid="!!props.errors.phone?.[0]"
                />
                <InputError :message="props.errors.phone?.[0]" />
            </div>

            <Button type="submit" class="w-full" :disabled="props.processing">
                <Spinner v-if="props.processing" />
                {{ t('auth.login.otpSendCode') }}
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.login.noAccount') }}
            <TextLink :to="{ name: 'register' }">{{
                t('auth.login.signUp')
            }}</TextLink>
        </div>
    </form>
</template>
