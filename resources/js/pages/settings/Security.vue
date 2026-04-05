<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import ConnectedAccounts from '@/components/settings/ConnectedAccounts.vue';
import TwoFactorSettings from '@/components/settings/TwoFactorSettings.vue';
import UpdatePasswordForm from '@/components/settings/UpdatePasswordForm.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const auth = useAuthStore();

const breadcrumbs = computed(() => [
    {
        title: t('settings.breadcrumbSettings'),
        href: { name: 'settings.profile' } as const,
    },
    {
        title: t('settings.security'),
        href: { name: 'settings.security' } as const,
    },
]);

const twoFactorEnabled = computed(() => auth.user?.two_factor_enabled === true);
const hasPassword = computed(() => auth.user?.has_password !== false);

const googleLinked = computed(() => auth.user?.google_linked === true);
const githubLinked = computed(() => auth.user?.github_linked === true);

const userHasPassword = computed(() => auth.user?.has_password === true);

/** Require password or another social provider before unlinking. */
const canUnlinkGoogle = computed(
    () => googleLinked.value && (userHasPassword.value || githubLinked.value),
);
const canUnlinkGithub = computed(
    () => githubLinked.value && (userHasPassword.value || googleLinked.value),
);
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <h1 class="sr-only">{{ t('security.pageTitle') }}</h1>

            <div class="space-y-10">
                <UpdatePasswordForm :has-password="hasPassword" />

                <div class="border-t border-border pt-10">
                    <TwoFactorSettings
                        :two-factor-enabled="twoFactorEnabled"
                        :has-password="hasPassword"
                    />
                </div>

                <div class="border-t border-border pt-10">
                    <ConnectedAccounts
                        :google-linked="googleLinked"
                        :github-linked="githubLinked"
                        :can-unlink-google="canUnlinkGoogle"
                        :can-unlink-github="canUnlinkGithub"
                    />
                </div>
            </div>
        </SettingsLayout>
    </AppSidebarLayout>
</template>
