export type CommitPendingAvatarResult =
    | { ok: false }
    | { ok: true; pendingUploaded: false }
    | { ok: true; pendingUploaded: true; avatarUrl: string };
