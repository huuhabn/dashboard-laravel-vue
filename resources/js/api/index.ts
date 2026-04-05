/**
 * API client surface for the Vue SPA.
 *
 * - {@link http} — axios instance, default choice for components.
 * - Token helpers — persist Sanctum personal access token.
 *
 * Fetch-based client (`apiFetch` / `apiJson`) is available via direct import
 * from `@/api/fetch-client` when needed (e.g. streaming responses).
 */
export { http } from '@/api/http';
export { clearAccessToken, getAccessToken, setAccessToken } from '@/api/token';
