/**
 * Reads `avatar` from Laravel JsonResource-style JSON (`{ data: { avatar } }`) or a flat user object.
 */
export function extractAvatarFromApiResponseBody(body: unknown): string {
    if (!body || typeof body !== 'object') {
        return '';
    }

    const root = body as Record<string, unknown>;
    const data = root.data;

    if (data && typeof data === 'object') {
        const avatar = (data as Record<string, unknown>).avatar;

        if (typeof avatar === 'string' && avatar.trim() !== '') {
            return avatar.trim();
        }
    }

    if (typeof root.avatar === 'string' && root.avatar.trim() !== '') {
        return root.avatar.trim();
    }

    return '';
}
