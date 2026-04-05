<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { http } from '@/api';
import PlaceholderPattern from '@/components/common/PlaceholderPattern.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

type DashboardPayload = {
    welcome_title: string;
    panels: Array<{ id: number; title: string }>;
};

const { t } = useI18n();

const overview = ref<DashboardPayload | null>(null);
const loadError = ref('');

const breadcrumbs = computed(() => [
    { title: t('dashboard.breadcrumb'), href: { name: 'dashboard' } as const },
]);

onMounted(async () => {
    try {
        const { data } = await http.get<DashboardPayload>('/dashboard');
        overview.value = data;
    } catch {
        loadError.value = t('dashboard.loadError');
    }
});
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <p
                v-if="overview?.welcome_title"
                class="text-lg font-medium text-foreground"
            >
                {{ overview.welcome_title }}
            </p>
            <p v-if="loadError" class="text-sm text-destructive">
                {{ loadError }}
            </p>

            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppSidebarLayout>
</template>
