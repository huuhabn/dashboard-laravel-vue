<script setup lang="ts">
import { Palette, Shield, User } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import Heading from '@/components/common/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

const { t } = useI18n();

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('settings.profile'),
        href: { name: 'settings.profile' },
        icon: User,
    },
    {
        title: t('settings.security'),
        href: { name: 'settings.security' },
        icon: Shield,
    },
    {
        title: t('settings.appearance'),
        href: { name: 'settings.appearance' },
        icon: Palette,
    },
]);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="t('settings.title')"
            :description="t('settings.description')"
        />

        <div class="flex flex-col gap-0 lg:flex-row lg:gap-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col gap-1"
                    :aria-label="t('settings.navAria')"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <RouterLink
                            :to="item.href"
                            class="flex w-full items-center gap-2 text-start"
                        >
                            <component
                                :is="item.icon"
                                v-if="item.icon"
                                class="size-4 shrink-0"
                            />
                            <span class="min-w-0 truncate">{{
                                item.title
                            }}</span>
                        </RouterLink>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
