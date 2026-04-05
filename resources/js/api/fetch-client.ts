import { API_V1_BASE_URL } from '@/api/config';
import { getAccessToken } from '@/api/token';
import { buildAcceptLanguageHeader } from '@/i18n/locale-storage';

export type ApiValidationBody = {
    message?: string;
    errors?: Record<string, string[]>;
};

/** Thrown when `apiFetch` / `apiJson` receives a non-OK response. */
export class ApiError extends Error {
    readonly status: number;

    readonly body: unknown;

    constructor(message: string, status: number, body: unknown) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.body = body;
    }

    /** Laravel validation errors when present. */
    get validationErrors(): Record<string, string[]> | undefined {
        if (
            this.body &&
            typeof this.body === 'object' &&
            'errors' in this.body
        ) {
            return (this.body as ApiValidationBody).errors;
        }

        return undefined;
    }
}

function normalizePath(path: string): string {
    return path.startsWith('/') ? path : `/${path}`;
}

/**
 * `fetch` wrapper with the same base URL and Bearer token behavior as {@link http}.
 * Use when you need Fetch APIs (streams, Request/Response) alongside axios.
 */
export async function apiFetch(
    path: string,
    init: RequestInit = {},
): Promise<Response> {
    const url = `${API_V1_BASE_URL}${normalizePath(path)}`;
    const headers = new Headers(init.headers);

    if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
    }

    const isFormData =
        typeof FormData !== 'undefined' && init.body instanceof FormData;

    if (
        !isFormData &&
        init.body !== undefined &&
        init.body !== null &&
        !headers.has('Content-Type')
    ) {
        headers.set('Content-Type', 'application/json');
    }

    const token = getAccessToken();

    if (token) {
        headers.set('Authorization', `Bearer ${token}`);
    }

    headers.set('Accept-Language', buildAcceptLanguageHeader());
    headers.set('X-Requested-With', 'XMLHttpRequest');

    return fetch(url, {
        ...init,
        headers,
    });
}

/**
 * Parses JSON from a successful response; throws {@link ApiError} on failure.
 * Returns `undefined` for 204 No Content.
 */
export async function apiJson<T>(path: string, init?: RequestInit): Promise<T> {
    const response = await apiFetch(path, init);
    const text = response.status === 204 ? '' : await response.text();
    let data: unknown = null;

    if (text) {
        try {
            data = JSON.parse(text) as unknown;
        } catch {
            data = { raw: text };
        }
    }

    if (!response.ok) {
        const message =
            data &&
            typeof data === 'object' &&
            'message' in data &&
            typeof (data as { message: unknown }).message === 'string'
                ? (data as { message: string }).message
                : response.statusText;

        throw new ApiError(message, response.status, data);
    }

    return data as T;
}
