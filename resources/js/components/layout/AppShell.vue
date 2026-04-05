<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { SidebarProvider } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const { locale } = useI18n();

/**
 * Document `dir="rtl"` reverses flex main-start/end, which breaks `flex-1` + DOM order
 * for [Sidebar, Inset]. Use LTR on the shell and row-reverse so geometry matches shadcn,
 * while AppSidebar/AppContent re-apply `dir="rtl"` for Arabic text.
 */
const sidebarProviderAttrs = computed(() => {
    if (props.variant !== 'sidebar' || locale.value !== 'ar') {
        return {};
    }

    return {
        dir: 'ltr' as const,
        class: cn('flex-row-reverse'),
    };
});

const sidebarOpen = ref(true);

onMounted(() => {
    const match = document.cookie.match(/sidebar_state=([^;]+)/);

    if (match) {
        sidebarOpen.value = match[1] === 'true';
    }
});
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider
        v-else
        :default-open="sidebarOpen"
        v-bind="sidebarProviderAttrs"
    >
        <slot />
    </SidebarProvider>
</template>
