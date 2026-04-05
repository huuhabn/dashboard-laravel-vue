/** Persisted SPA locale; default and fallback is English. */
export const LOCALE_STORAGE_KEY = 'app_locale';

export const SUPPORTED_LOCALES = ['en', 'vi', 'ar'] as const;

export type LocaleCode = (typeof SUPPORTED_LOCALES)[number];

export function isLocaleCode(value: string): value is LocaleCode {
    return SUPPORTED_LOCALES.includes(value as LocaleCode);
}

export function getInitialLocale(): LocaleCode {
    if (typeof localStorage === 'undefined') {
        return 'en';
    }

    const raw = localStorage.getItem(LOCALE_STORAGE_KEY);

    if (raw && isLocaleCode(raw)) {
        return raw;
    }

    return 'en';
}

/** Re-read before each API request so the header stays in sync after switching language. */
export function getStoredLocaleForRequest(): LocaleCode {
    return getInitialLocale();
}

export function persistLocale(code: LocaleCode): void {
    if (typeof localStorage === 'undefined') {
        return;
    }

    localStorage.setItem(LOCALE_STORAGE_KEY, code);
}

export function buildAcceptLanguageHeader(): string {
    const primary = getStoredLocaleForRequest();

    return `${primary},en;q=0.8`;
}
