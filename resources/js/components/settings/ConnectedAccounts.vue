<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { http } from '@/api';
import Heading from '@/components/common/Heading.vue';
import InputError from '@/components/forms/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useAuthStore } from '@/stores/auth';

const props = defineProps<{
    googleLinked: boolean;
    githubLinked: boolean;
    canUnlinkGoogle: boolean;
    canUnlinkGithub: boolean;
}>();

const { t } = useI18n();
const auth = useAuthStore();

const socialErrors = ref<Record<string, string[]>>({});
const socialUnlinkOtherError = ref('');
const unlinking = ref<'google' | 'github' | null>(null);

async function unlinkProvider(provider: 'google' | 'github'): Promise<void> {
    unlinking.value = provider;
    socialErrors.value = {};
    socialUnlinkOtherError.value = '';

    try {
        await http.delete(`/me/social/${provider}`);
        await auth.fetchUser();
    } catch (e: unknown) {
        const err = e as {
            response?: {
                data?: { errors?: Record<string, string[]>; message?: string };
            };
        };

        if (err.response?.data?.errors) {
            socialErrors.value = err.response.data.errors;
        } else if (typeof err.response?.data?.message === 'string') {
            socialUnlinkOtherError.value = err.response.data.message;
        }
    } finally {
        unlinking.value = null;
    }
}
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('security.connectedAccountsHeading')"
            :description="t('security.connectedAccountsDescription')"
        />

        <p class="text-sm text-muted-foreground">
            {{ t('security.connectedAccountsHint') }}
        </p>

        <ul class="max-w-md space-y-3">
            <li
                class="flex flex-col gap-2 rounded-lg border border-border px-3 py-2"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-medium">Google</span>
                    <div class="flex items-center gap-2">
                        <span
                            v-if="props.googleLinked"
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('security.providerLinked') }}
                        </span>
                        <span v-else class="text-xs text-muted-foreground">
                            {{ t('security.providerNotLinked') }}
                        </span>
                        <Button
                            v-if="props.googleLinked"
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="
                                !props.canUnlinkGoogle || unlinking != null
                            "
                            @click="unlinkProvider('google')"
                        >
                            <Spinner v-if="unlinking === 'google'" />
                            {{ t('security.unlinkProvider') }}
                        </Button>
                    </div>
                </div>
                <p
                    v-if="props.googleLinked && !props.canUnlinkGoogle"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('security.unlinkBlockedGoogleHint') }}
                </p>
            </li>
            <li
                class="flex flex-col gap-2 rounded-lg border border-border px-3 py-2"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-medium">GitHub</span>
                    <div class="flex items-center gap-2">
                        <span
                            v-if="props.githubLinked"
                            class="text-xs text-muted-foreground"
                        >
                            {{ t('security.providerLinked') }}
                        </span>
                        <span v-else class="text-xs text-muted-foreground">
                            {{ t('security.providerNotLinked') }}
                        </span>
                        <Button
                            v-if="props.githubLinked"
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="
                                !props.canUnlinkGithub || unlinking != null
                            "
                            @click="unlinkProvider('github')"
                        >
                            <Spinner v-if="unlinking === 'github'" />
                            {{ t('security.unlinkProvider') }}
                        </Button>
                    </div>
                </div>
                <p
                    v-if="props.githubLinked && !props.canUnlinkGithub"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('security.unlinkBlockedGithubHint') }}
                </p>
            </li>
        </ul>

        <InputError :message="socialErrors.provider?.[0]" />
        <InputError :message="socialUnlinkOtherError" />
    </div>
</template>
