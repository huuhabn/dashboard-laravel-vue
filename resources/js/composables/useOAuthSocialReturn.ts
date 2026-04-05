import type { RouteLocationNormalizedLoaded, Router } from 'vue-router';
import { http } from '@/api';
import { dashboardAppPath } from '@/config/dashboardPrefix';

export type SocialExchangeOutcome =
    | { kind: 'token'; token: string; user: unknown }
    | { kind: 'two_factor'; pending_token: string };

/**
 * POST /auth/social/exchange — shared by login and register after OAuth redirect.
 */
export async function postSocialExchange(
    exchangeToken: string,
): Promise<
    | { ok: true; data: SocialExchangeOutcome }
    | { ok: false; errors?: Record<string, string[]> }
> {
    try {
        const { data } = await http.post<{
            token?: string;
            two_factor_required?: boolean;
            pending_token?: string;
            user?: unknown;
        }>('/auth/social/exchange', {
            exchange_token: exchangeToken,
            device_name: 'spa',
        });

        if (data.two_factor_required && data.pending_token) {
            return {
                ok: true,
                data: { kind: 'two_factor', pending_token: data.pending_token },
            };
        }

        if (typeof data.token === 'string' && data.token) {
            return {
                ok: true,
                data: { kind: 'token', token: data.token, user: data.user },
            };
        }

        return { ok: false };
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        return {
            ok: false,
            errors: err.response?.data?.errors,
        };
    }
}

export async function stripSocialExchangeQuery(
    router: Router,
    route: RouteLocationNormalizedLoaded,
): Promise<void> {
    const q = { ...route.query };
    delete q.social_exchange;
    await router.replace({ query: q });
}

export async function completeOAuthTwoFactor(
    pendingToken: string,
    code: string,
): Promise<
    | { ok: true; token: string; user: unknown }
    | { ok: false; errors?: Record<string, string[]> }
> {
    try {
        const { data } = await http.post<{
            token?: string;
            user?: unknown;
        }>('/auth/token/two-factor', {
            pending_token: pendingToken,
            code: code.replace(/\s/g, ''),
            device_name: 'spa',
        });

        if (typeof data.token !== 'string' || !data.token) {
            return { ok: false };
        }

        return { ok: true, token: data.token, user: data.user };
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        return {
            ok: false,
            errors: err.response?.data?.errors,
        };
    }
}

export function defaultPathAfterOAuthAuth(): string {
    return dashboardAppPath('dashboard');
}
