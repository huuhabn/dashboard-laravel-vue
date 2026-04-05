<script setup lang="ts">
import { ChevronsUpDown } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import UserInfo from '@/components/user/UserInfo.vue';
import UserMenuContent from '@/components/user/UserMenuContent.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const { user } = storeToRefs(auth);
const safeUser = computed(() => user.value);
const { isMobile, state } = useSidebar();
const { locale } = useI18n();

const isRtl = computed(() => locale.value === 'ar');

/** Collapsed sidebar: open menu toward page content (flip in RTL). */
const dropdownSide = computed(() => {
    if (isMobile.value) {
        return 'bottom';
    }

    if (state.value === 'collapsed') {
        return isRtl.value ? 'right' : 'left';
    }

    return 'bottom';
});

/**
 * reka-ui reads direction from DropdownMenuRoot (and ConfigProvider), not from
 * DropdownMenuContent. Without a global ConfigProvider, menus default to LTR.
 */
const menuDir = computed(() => (isRtl.value ? 'rtl' : undefined));
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu :dir="menuDir">
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        data-test="sidebar-menu-button"
                    >
                        <UserInfo v-if="safeUser" :user="safeUser" />
                        <ChevronsUpDown class="ms-auto size-4 shrink-0" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="dropdownSide"
                    align="end"
                    :side-offset="4"
                >
                    <UserMenuContent v-if="safeUser" :user="safeUser" />
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
