<script setup lang="ts">
import { RouterLink } from 'vue-router';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();

function isExternal(href: NavItem['href']): boolean {
    return typeof href === 'string' && /^https?:\/\//.test(href);
}

/** Plain <a> — not a Vue route (avoids RouterLink "no match" for /storage/... etc.). */
function isPublicAssetPath(href: NavItem['href']): boolean {
    return (
        typeof href === 'string' &&
        (href.startsWith('/storage/') || href.startsWith('/build/'))
    );
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <a
                        v-if="
                            isExternal(item.href) ||
                            isPublicAssetPath(item.href)
                        "
                        :href="String(item.href)"
                        :target="isExternal(item.href) ? '_blank' : undefined"
                        :rel="
                            isExternal(item.href)
                                ? 'noopener noreferrer'
                                : undefined
                        "
                    >
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </a>
                    <RouterLink v-else :to="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </RouterLink>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
