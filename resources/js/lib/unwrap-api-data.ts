/**
 * Unwraps Laravel-style `{ data: T }` JSON, or returns the root object as T.
 */
export function unwrapApiData<T>(body: unknown): T | null {
    if (!body || typeof body !== 'object') {
        return null;
    }

    const root = body as Record<string, unknown>;
    const inner = root.data;

    if (
        inner !== null &&
        inner !== undefined &&
        typeof inner === 'object' &&
        !Array.isArray(inner)
    ) {
        return inner as T;
    }

    return root as T;
}
