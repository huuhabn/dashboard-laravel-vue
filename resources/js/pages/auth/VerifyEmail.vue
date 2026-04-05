<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { http } from '@/api';
import TextLink from '@/components/common/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const auth = useAuthStore();
const router = useRouter();
const processing = ref(false);
const status = ref('');

async function resend() {
    processing.value = true;
    status.value = '';

    try {
        const { data } = await http.post('/auth/email/resend');
        status.value = data.message ?? '';
    } finally {
        processing.value = false;
    }
}

async function logout() {
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <AuthLayout
        :title="t('auth.verify.title')"
        :description="t('auth.verify.description')"
    >
        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <div
            class="flex flex-col gap-4 text-center text-sm text-muted-foreground"
        >
            <Button :disabled="processing" @click="resend">
                <Spinner v-if="processing" />
                {{ t('auth.verify.resend') }}
            </Button>
            <button
                type="button"
                class="mx-auto text-sm underline-offset-4 hover:underline"
                @click="logout"
            >
                {{ t('auth.verify.logout') }}
            </button>
            <TextLink :to="{ name: 'dashboard' }">{{
                t('auth.verify.backToDashboard')
            }}</TextLink>
        </div>
    </AuthLayout>
</template>
