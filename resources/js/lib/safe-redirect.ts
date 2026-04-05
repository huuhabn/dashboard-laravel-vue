import type { LocationQueryValue } from 'vue-router';

export function firstQueryValue(
    value: LocationQueryValue | LocationQueryValue[] | undefined,
): string | undefined {
    if (value === null || value === undefined) {
        return undefined;
    }

    if (Array.isArray(value)) {
        const first = value[0];

        return typeof first === 'string' ? first : undefined;
    }

    return typeof value === 'string' ? value : undefined;
}

/**
 * Internal path only — rejects protocol-relative and absolute URLs (open-redirect hardening).
 */
export function safeAuthRedirectPath(
    raw: LocationQueryValue | LocationQueryValue[] | undefined,
    fallback: string,
): string {
    const s = firstQueryValue(raw)?.trim();

    if (!s) {
        return fallback;
    }

    if (!s.startsWith('/') || s.startsWith('//')) {
        return fallback;
    }

    return s;
}
