<script setup lang="ts">
import { Folder, HelpCircle, LayoutGrid } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import AppLogo from '@/components/brand/AppLogo.vue';
import NavFooter from '@/components/navigation/NavFooter.vue';
import NavMain from '@/components/navigation/NavMain.vue';
import NavUser from '@/components/navigation/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const { t, locale } = useI18n();

const sidebarSide = computed(() => (locale.value === 'ar' ? 'right' : 'left'));

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: { name: 'dashboard' },
        icon: LayoutGrid,
    },
]);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.repository'),
        href: 'https://github.com/vexaltrix/laravel-vue-starter',
        icon: Folder,
    },
    {
        title: t('nav.helpCenter'),
        href: '#help-center',
        icon: HelpCircle,
    },
]);
</script>

<template>
    <!-- Re-apply RTL for nav text when shell uses dir=ltr + flex-row-reverse (see AppShell). -->
    <Sidebar
        :side="sidebarSide"
        collapsible="icon"
        variant="inset"
        :dir="locale === 'ar' ? 'rtl' : undefined"
    >
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <RouterLink :to="{ name: 'dashboard' }">
                            <AppLogo />
                        </RouterLink>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
