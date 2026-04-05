/**
 * Persists the Sanctum personal access token for SPA requests.
 * Uses localStorage so sessions survive refresh; adjust if you need httpOnly cookies.
 */
const TOKEN_STORAGE_KEY = 'auth_token';

export function getAccessToken(): string | null {
    if (typeof localStorage === 'undefined') {
        return null;
    }

    return localStorage.getItem(TOKEN_STORAGE_KEY);
}

/**
 * @param token Pass null or empty string to remove the stored token.
 */
export function setAccessToken(token: string | null): void {
    if (typeof localStorage === 'undefined') {
        return;
    }

    if (token === null || token === '') {
        localStorage.removeItem(TOKEN_STORAGE_KEY);

        return;
    }

    localStorage.setItem(TOKEN_STORAGE_KEY, token);
}

export function clearAccessToken(): void {
    setAccessToken(null);
}
