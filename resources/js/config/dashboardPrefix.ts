/**
 * URL segment for dashboard + settings area (from Laravel `DASHBOARD_PREFIX`, default `admin`).
 * Set on `window` in `app.blade.php` before the Vite bundle loads.
 */
export function readDashboardPrefixSegment(): string {
    if (typeof window === 'undefined') {
        return 'admin';
    }

    const raw = (window as unknown as { __DASHBOARD_PREFIX__?: string })
        .__DASHBOARD_PREFIX__;

    if (typeof raw !== 'string') {
        return 'admin';
    }

    const segment = raw.trim().replace(/^\/+|\/+$/g, '');

    return segment !== '' ? segment : 'admin';
}

/** Base path without trailing slash, e.g. `/admin`. */
export function dashboardBasePath(): string {
    return `/${readDashboardPrefixSegment()}`;
}

/** Full path under the dashboard prefix, e.g. `/admin/dashboard`. */
export function dashboardAppPath(relativePath: string): string {
    const rel = relativePath.replace(/^\/+/, '');

    return `${dashboardBasePath()}/${rel}`;
}
