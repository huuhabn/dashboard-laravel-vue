import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { RouteLocationRaw } from 'vue-router';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: RouteLocationRaw): string {
    if (typeof href === 'string') {
        return href;
    }

    if (typeof href === 'object' && href !== null) {
        if ('name' in href && href.name != null) {
            const n = href.name;

            return typeof n === 'symbol' ? String(n) : `route:${String(n)}`;
        }

        if ('path' in href) {
            const p = (href as { path?: string }).path;

            return p ?? '/';
        }
    }

    return '/';
}
