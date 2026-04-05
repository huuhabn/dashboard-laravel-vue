<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { http } from '@/api';
import InputError from '@/components/forms/InputError.vue';
import PasswordInput from '@/components/forms/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const token = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const status = ref('');

onMounted(() => {
    token.value = (route.query.token as string) || '';
    email.value = (route.query.email as string) || '';
});

async function submit() {
    processing.value = true;
    errors.value = {};
    status.value = '';

    try {
        const { data } = await http.post('/auth/reset-password', {
            token: token.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        status.value = data.message ?? '';
        await router.push({ name: 'login' });
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
    <AuthLayout
        :title="t('auth.reset.title')"
        :description="t('auth.reset.description')"
    >
        <form class="grid gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="email">{{ t('auth.reset.email') }}</Label>
                <Input
                    id="email"
                    v-model="email"
                    type="email"
                    required
                    autocomplete="username"
                />
                <InputError :message="errors.email?.[0]" />
            </div>

            <div class="grid gap-2">
                <Label for="password">{{ t('auth.reset.password') }}</Label>
                <PasswordInput
                    id="password"
                    v-model="password"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password?.[0]" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('auth.reset.confirmPassword')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="passwordConfirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password_confirmation?.[0]" />
            </div>

            <Button type="submit" class="mt-4 w-full" :disabled="processing">
                <Spinner v-if="processing" />
                {{ t('auth.reset.submit') }}
            </Button>
        </form>
    </AuthLayout>
</template>
