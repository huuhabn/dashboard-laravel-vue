import { createI18n } from 'vue-i18n';
import { getInitialLocale } from '@/i18n/locale-storage';
import ar from '@/locales/ar.json';
import en from '@/locales/en.json';
import vi from '@/locales/vi.json';

export const i18n = createI18n({
    legacy: false,
    locale: getInitialLocale(),
    fallbackLocale: 'en',
    globalInjection: true,
    messages: {
        en,
        vi,
        ar,
    },
});

export {
    getInitialLocale,
    persistLocale,
    SUPPORTED_LOCALES,
} from '@/i18n/locale-storage';
export type { LocaleCode } from '@/i18n/locale-storage';
