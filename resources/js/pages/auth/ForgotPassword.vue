<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { http } from '@/api';
import TextLink from '@/components/common/TextLink.vue';
import InputError from '@/components/forms/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { t } = useI18n();

const email = ref('');
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const status = ref('');

async function submit() {
    processing.value = true;
    errors.value = {};
    status.value = '';

    try {
        const { data } = await http.post('/auth/forgot-password', {
            email: email.value,
        });
        status.value = data.message ?? '';
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
        :title="t('auth.forgot.title')"
        :description="t('auth.forgot.description')"
    >
        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <form class="grid gap-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="email">{{ t('auth.login.email') }}</Label>
                <Input
                    id="email"
                    v-model="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    :placeholder="t('auth.login.emailPlaceholder')"
                />
                <InputError :message="errors.email?.[0]" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button class="w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    {{ t('auth.forgot.submit') }}
                </Button>
            </div>
        </form>

        <div class="text-center text-sm text-muted-foreground">
            <TextLink :to="{ name: 'login' }">{{
                t('auth.forgot.backToLogin')
            }}</TextLink>
        </div>
    </AuthLayout>
</template>
