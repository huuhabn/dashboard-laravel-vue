<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import TextLink from '@/components/common/TextLink.vue';
import InputError from '@/components/forms/InputError.vue';
import PasswordInput from '@/components/forms/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import type { SocialProviders } from '@/stores/authConfig';

const props = defineProps<{
    processing: boolean;
    errors: Record<string, string[]>;
    showSocial: boolean;
    socialProviders: SocialProviders;
}>();

const emit = defineEmits<{
    submit: [credentials: { email: string; password: string }];
    social: [provider: 'google' | 'github'];
}>();

const { t } = useI18n();

const email = ref('');
const password = ref('');

function onSubmit() {
    emit('submit', { email: email.value, password: password.value });
}

defineExpose({
    clearPassword: () => {
        password.value = '';
    },
});
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{{ t('auth.login.email') }}</Label>
                <Input
                    id="email"
                    v-model="email"
                    type="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    :placeholder="t('auth.login.emailPlaceholder')"
                />
                <InputError :message="props.errors.email?.[0]" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">{{ t('auth.login.password') }}</Label>
                    <TextLink
                        :to="{ name: 'forgot-password' }"
                        class="text-sm"
                        :tabindex="5"
                    >
                        {{ t('auth.login.forgot') }}
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    v-model="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :placeholder="t('auth.login.passwordPlaceholder')"
                />
                <InputError :message="props.errors.password?.[0]" />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="props.processing"
                data-test="login-button"
            >
                <Spinner v-if="props.processing" />
                {{ t('auth.login.submit') }}
            </Button>
        </div>

        <template v-if="props.showSocial">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <Separator />
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-background px-2 text-muted-foreground">
                        {{ t('auth.login.socialDivider') }}
                    </span>
                </div>
            </div>

            <div class="grid gap-2">
                <Button
                    v-if="props.socialProviders.google"
                    type="button"
                    variant="outline"
                    class="w-full"
                    :disabled="props.processing"
                    @click="emit('social', 'google')"
                >
                    {{ t('auth.login.socialGoogle') }}
                </Button>
                <Button
                    v-if="props.socialProviders.github"
                    type="button"
                    variant="outline"
                    class="w-full"
                    :disabled="props.processing"
                    @click="emit('social', 'github')"
                >
                    {{ t('auth.login.socialGithub') }}
                </Button>
            </div>
        </template>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('auth.login.noAccount') }}
            <TextLink :to="{ name: 'register' }" :tabindex="5">{{
                t('auth.login.signUp')
            }}</TextLink>
        </div>
    </form>
</template>
