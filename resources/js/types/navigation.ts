import type { LucideIcon } from 'lucide-vue-next';
import type { RouteLocationRaw } from 'vue-router';

export type BreadcrumbItem = {
    title: string;
    href: RouteLocationRaw;
};

export type NavItem = {
    title: string;
    href: RouteLocationRaw;
    icon?: LucideIcon;
    isActive?: boolean;
};
