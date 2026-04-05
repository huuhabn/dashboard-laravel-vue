<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router';
import { RouterLink } from 'vue-router';

defineProps<{
    to: RouteLocationRaw;
    tabindex?: number;
}>();
</script>

<template>
    <RouterLink
        :to="to"
        :tabindex="tabindex"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
    >
        <slot />
    </RouterLink>
</template>
