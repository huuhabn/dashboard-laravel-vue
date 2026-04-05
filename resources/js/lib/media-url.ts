/**
 * Origin for resolving root-relative Laravel public URLs (/storage/...) when the SPA
 * is opened on a different host/port than the API (e.g. Vite on :5173).
 */
function publicAssetOrigin(): string {
    if (typeof window === 'undefined') {
        return '';
    }

    const fromEnv = import.meta.env.VITE_BACKEND_URL;

    if (typeof fromEnv === 'string' && fromEnv.trim() !== '') {
        try {
            return new URL(fromEnv.trim()).origin;
        } catch {
            /* Invalid VITE_BACKEND_URL — fall back to page origin. */
        }
    }

    return window.location.origin;
}

/**
 * Normalizes stored avatar / media URLs for <img src>.
 * Supports absolute http(s), protocol-relative (//host/...), and root-relative (/...).
 */
export function resolvePublicImageUrl(raw: string | null | undefined): string {
    if (raw == null) {
        return '';
    }

    const v = String(raw).trim();

    if (!v) {
        return '';
    }

    if (/^https?:\/\//i.test(v)) {
        return v;
    }

    if (v.startsWith('//')) {
        return `https:${v}`;
    }

    if (v.startsWith('/')) {
        if (typeof window === 'undefined') {
            return v;
        }

        const origin = publicAssetOrigin();

        if (!origin) {
            return v;
        }

        return new URL(v, `${origin}/`).href;
    }

    return '';
}
