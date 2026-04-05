export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    /** Optional; API may omit — UI falls back to initials. */
    avatar?: string | null;
    /** E.164 when set */
    phone?: string | null;
    has_password?: boolean;
    two_factor_enabled?: boolean;
    google_linked?: boolean;
    github_linked?: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface Auth {
    user: User;
}
