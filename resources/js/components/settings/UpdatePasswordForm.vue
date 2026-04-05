<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { http } from '@/api';
import Heading from '@/components/common/Heading.vue';
import InputError from '@/components/forms/InputError.vue';
import PasswordInput from '@/components/forms/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useAuthStore } from '@/stores/auth';

const props = defineProps<{
    hasPassword: boolean;
}>();

const { t } = useI18n();
const auth = useAuthStore();

const currentPassword = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const recentlySuccessful = ref(false);

async function submitPassword() {
    processing.value = true;
    errors.value = {};
    recentlySuccessful.value = false;

    try {
        await http.put('/me/password', {
            current_password: currentPassword.value || undefined,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        currentPassword.value = '';
        password.value = '';
        passwordConfirmation.value = '';
        recentlySuccessful.value = true;
        await auth.fetchUser();
        setTimeout(() => {
            recentlySuccessful.value = false;
        }, 2500);
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
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('security.passwordSectionTitle')"
            :description="
                props.hasPassword
                    ? t('security.headingDescription')
                    : t('security.passwordOAuthHint')
            "
        />

        <form class="space-y-6" @submit.prevent="submitPassword">
            <div v-if="props.hasPassword" class="grid gap-2">
                <Label for="current_password">{{
                    t('security.currentPassword')
                }}</Label>
                <PasswordInput
                    id="current_password"
                    v-model="currentPassword"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    :placeholder="t('security.currentPlaceholder')"
                />
                <InputError :message="errors.current_password?.[0]" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ t('security.newPassword') }}</Label>
                <PasswordInput
                    id="password"
                    v-model="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    :placeholder="t('security.newPlaceholder')"
                />
                <InputError :message="errors.password?.[0]" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('security.confirmPassword')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="passwordConfirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    :placeholder="t('security.confirmPlaceholder')"
                />
                <InputError :message="errors.password_confirmation?.[0]" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('security.savePassword') }}
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-show="recentlySuccessful"
                        class="text-sm text-neutral-600"
                    >
                        {{ t('security.saved') }}
                    </p>
                </Transition>
            </div>
        </form>
    </div>
</template>
