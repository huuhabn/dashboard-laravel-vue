<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppearanceTabs from '@/components/common/AppearanceTabs.vue';
import Heading from '@/components/common/Heading.vue';
import LocaleSwitcher from '@/components/common/LocaleSwitcher.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const { t } = useI18n();

const breadcrumbs = computed(() => [
    {
        title: t('settings.breadcrumbSettings'),
        href: { name: 'settings.profile' } as const,
    },
    {
        title: t('settings.appearance'),
        href: { name: 'settings.appearance' } as const,
    },
]);
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <h1 class="sr-only">{{ t('appearancePage.pageTitle') }}</h1>

            <div class="space-y-6">
                <Heading
                    variant="small"
                    :title="t('appearancePage.headingTitle')"
                    :description="t('appearancePage.headingDescription')"
                />
                <AppearanceTabs />
            </div>

            <div class="space-y-6 border-t border-border pt-10">
                <Heading
                    variant="small"
                    :title="t('appearancePage.languageTitle')"
                    :description="t('appearancePage.languageDescription')"
                />
                <LocaleSwitcher />
            </div>
        </SettingsLayout>
    </AppSidebarLayout>
</template>
