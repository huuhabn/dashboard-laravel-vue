<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});
const { locale } = useI18n();

const insetClass = computed(() =>
    cn(
        'min-w-0',
        // Shell is dir=ltr + row-reverse for ar; inset margins must use the edge toward the peer (me-*).
        locale.value === 'ar' &&
            'md:peer-data-[variant=inset]:ms-2 md:peer-data-[variant=inset]:me-0 md:peer-data-[variant=inset]:peer-data-[state=collapsed]:me-2',
        props.class,
    ),
);
</script>

<template>
    <SidebarInset
        v-if="props.variant === 'sidebar'"
        :class="insetClass"
        :dir="locale === 'ar' ? 'rtl' : undefined"
    >
        <slot />
    </SidebarInset>
    <main
        v-else
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
        :class="props.class"
    >
        <slot />
    </main>
</template>
