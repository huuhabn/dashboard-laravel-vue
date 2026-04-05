<script setup lang="ts">
import { RouterLink } from 'vue-router';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

defineProps<Props>();

function isExternal(href: BreadcrumbItemType['href']): boolean {
    return typeof href === 'string' && /^https?:\/\//.test(href);
}

function isPublicAssetPath(href: BreadcrumbItemType['href']): boolean {
    return (
        typeof href === 'string' &&
        (href.startsWith('/storage/') || href.startsWith('/build/'))
    );
}
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem>
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                    </template>
                    <template v-else>
                        <BreadcrumbLink as-child>
                            <a
                                v-if="
                                    isExternal(item.href) ||
                                    isPublicAssetPath(item.href)
                                "
                                :href="String(item.href)"
                                :target="
                                    isExternal(item.href) ? '_blank' : undefined
                                "
                                :rel="
                                    isExternal(item.href)
                                        ? 'noopener noreferrer'
                                        : undefined
                                "
                                >{{ item.title }}</a
                            >
                            <RouterLink v-else :to="item.href">{{
                                item.title
                            }}</RouterLink>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
