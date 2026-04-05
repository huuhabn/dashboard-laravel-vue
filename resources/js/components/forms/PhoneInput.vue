<script setup lang="ts">
import {
    getCountries,
    getCountryCallingCode,
    parsePhoneNumberFromString,
} from 'libphonenumber-js';
import type { CountryCode } from 'libphonenumber-js';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        /** ISO 3166-1 alpha-2, e.g. VN */
        defaultRegion?: string;
        id?: string;
        label?: string;
        disabled?: boolean;
        invalid?: boolean;
    }>(),
    {
        defaultRegion: 'US',
        id: 'phone',
        label: '',
        disabled: false,
        invalid: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const { locale, t } = useI18n();

/** ISO 3166-1 alpha-2 → flag emoji (regional indicators). */
function countryCodeToFlagEmoji(iso2: string): string {
    const upper = iso2.toUpperCase();

    if (upper.length !== 2 || !/^[A-Z]{2}$/.test(upper)) {
        return '🏳️';
    }

    return String.fromCodePoint(
        ...[...upper].map((ch) => 0x1f1e6 + (ch.charCodeAt(0) - 65)),
    );
}

/** Shown first for quicker picking (still validated by libphonenumber). */
const PRIORITY_ISO: CountryCode[] = [
    'VN',
    'US',
    'GB',
    'SG',
    'JP',
    'KR',
    'AU',
    'DE',
    'FR',
    'IN',
    'CN',
    'TH',
    'MY',
    'ID',
    'PH',
    'TW',
    'HK',
    'CA',
    'NZ',
    'IT',
    'ES',
    'NL',
    'BE',
    'CH',
    'AE',
    'SA',
    'BR',
    'MX',
    'PL',
    'SE',
    'NO',
    'DK',
    'FI',
    'AT',
    'IE',
    'PT',
    'RU',
    'UA',
    'KZ',
    'BD',
    'PK',
    'EG',
    'ZA',
    'NG',
];

const allCountryCodes = getCountries() as CountryCode[];

function isCountryCode(v: string): v is CountryCode {
    return (allCountryCodes as string[]).includes(v);
}

function initialCountry(): CountryCode {
    const r = (props.defaultRegion ?? 'US').toUpperCase();

    return isCountryCode(r) ? r : 'US';
}

const selectedCountry = ref<CountryCode>(initialCountry());
const nationalNumber = ref('');
/** Cleared when the country dropdown closes. */
const countrySearchQuery = ref('');
const countrySelectOpen = ref(false);

type CountryRow = {
    code: CountryCode;
    dial: string;
    flag: string;
    label: string;
};

const countryGroups = computed(() => {
    const displayNames = new Intl.DisplayNames([locale.value], {
        type: 'region',
    });
    const allowed = new Set(allCountryCodes);

    const mk = (code: CountryCode): CountryRow => ({
        code,
        dial: getCountryCallingCode(code),
        flag: countryCodeToFlagEmoji(code),
        label: displayNames.of(code) ?? code,
    });

    const priority: CountryRow[] = [];

    for (const code of PRIORITY_ISO) {
        if (allowed.has(code)) {
            priority.push(mk(code));
        }
    }

    const priorityCodes = new Set(priority.map((p) => p.code));
    const restCodes = allCountryCodes
        .filter((c) => !priorityCodes.has(c))
        .sort((a, b) =>
            (displayNames.of(a) ?? a).localeCompare(
                displayNames.of(b) ?? b,
                locale.value,
                { sensitivity: 'base' },
            ),
        );

    return {
        priorityRows: priority,
        restRows: restCodes.map((c) => mk(c)),
    };
});

/** Match localized country name, ISO code, or calling code digits. */
function rowMatches(row: CountryRow, rawQuery: string): boolean {
    const q = rawQuery.trim();

    if (q === '') {
        return true;
    }

    const lower = q.toLowerCase();
    const digits = q.replace(/\D/g, '');

    if (row.label.toLowerCase().includes(lower)) {
        return true;
    }

    if (row.code.toLowerCase().includes(lower)) {
        return true;
    }

    if (digits.length > 0) {
        const d = row.dial;

        return d === digits || d.startsWith(digits) || digits.startsWith(d);
    }

    return false;
}

const isSearchActive = computed(
    () => countrySearchQuery.value.trim().length > 0,
);

const filteredRows = computed((): CountryRow[] => {
    if (!isSearchActive.value) {
        return [];
    }

    const q = countrySearchQuery.value;
    const merged: CountryRow[] = [...countryGroups.value.priorityRows];
    const seen = new Set(merged.map((r) => r.code));

    for (const r of countryGroups.value.restRows) {
        if (!seen.has(r.code)) {
            merged.push(r);
            seen.add(r.code);
        }
    }

    return merged
        .filter((r) => rowMatches(r, q))
        .sort((a, b) =>
            a.label.localeCompare(b.label, locale.value, {
                sensitivity: 'base',
            }),
        );
});

watch(countrySelectOpen, (open) => {
    if (!open) {
        countrySearchQuery.value = '';
    }
});

function applyExternalValue(e164: string): void {
    if (!e164?.trim()) {
        nationalNumber.value = '';

        return;
    }

    const parsed = parsePhoneNumberFromString(e164.trim());

    if (parsed?.country && parsed.nationalNumber) {
        selectedCountry.value = parsed.country;
        nationalNumber.value = parsed.nationalNumber;
    } else {
        nationalNumber.value = e164.replace(/\D/g, '');
    }
}

watch(
    () => props.modelValue,
    (v) => {
        applyExternalValue(typeof v === 'string' ? v : '');
    },
    { immediate: true },
);

watch(
    () => props.defaultRegion,
    (r) => {
        const u = r?.toUpperCase() ?? '';

        if (isCountryCode(u)) {
            selectedCountry.value = u;
        }
    },
);

function computeE164(): string {
    const digits = nationalNumber.value.replace(/\D/g, '');

    if (digits === '') {
        return '';
    }

    const pn = parsePhoneNumberFromString(digits, selectedCountry.value);

    return pn?.isValid() ? pn.format('E.164') : '';
}

watch([selectedCountry, nationalNumber], () => {
    const next = computeE164();

    if (next !== props.modelValue) {
        emit('update:modelValue', next);
    }
});

const callingCode = computed(() => {
    try {
        return `+${getCountryCallingCode(selectedCountry.value)}`;
    } catch {
        return '';
    }
});

const showInvalid = computed(
    () =>
        props.invalid ||
        (nationalNumber.value.trim() !== '' && props.modelValue === ''),
);

const selectedRow = computed((): CountryRow => {
    const code = selectedCountry.value;
    const inPriority = countryGroups.value.priorityRows.find(
        (r) => r.code === code,
    );

    if (inPriority) {
        return inPriority;
    }

    const inRest = countryGroups.value.restRows.find((r) => r.code === code);

    if (inRest) {
        return inRest;
    }

    const displayNames = new Intl.DisplayNames([locale.value], {
        type: 'region',
    });

    return {
        code,
        dial: getCountryCallingCode(code),
        flag: countryCodeToFlagEmoji(code),
        label: displayNames.of(code) ?? code,
    };
});

function onCountrySelect(value: unknown): void {
    if (value === null || value === undefined || value === '') {
        return;
    }

    const s = String(value);

    if (isCountryCode(s)) {
        selectedCountry.value = s;
    }
}
</script>

<template>
    <div class="grid gap-2">
        <Label v-if="label" :for="id">{{ label }}</Label>
        <div class="flex gap-2">
            <Select
                :model-value="selectedCountry"
                v-model:open="countrySelectOpen"
                :disabled="disabled"
                @update:model-value="onCountrySelect"
            >
                <SelectTrigger
                    :id="`${id}-country`"
                    :aria-label="t('phoneInput.countryAria')"
                    class="h-9 w-auto max-w-none shrink-0 gap-1.5 px-2"
                >
                    <SelectValue>
                        <span
                            v-if="selectedRow"
                            class="flex items-center gap-1.5 text-left"
                        >
                            <span
                                class="text-lg leading-none"
                                aria-hidden="true"
                                >{{ selectedRow.flag }}</span
                            >
                            <span
                                class="text-xs font-semibold tracking-wide uppercase"
                                >{{ selectedRow.code }}</span
                            >
                            <span
                                class="shrink-0 text-xs text-muted-foreground tabular-nums"
                                >+{{ selectedRow.dial }}</span
                            >
                        </span>
                    </SelectValue>
                </SelectTrigger>
                <SelectContent
                    class="max-h-[min(22rem,70dvh)] min-w-[var(--reka-select-trigger-width)] sm:min-w-[20rem]"
                >
                    <div
                        class="sticky top-0 z-10 border-b bg-popover p-1"
                        @pointerdown.stop
                    >
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                v-model="countrySearchQuery"
                                type="search"
                                :aria-label="t('phoneInput.searchAria')"
                                :placeholder="t('phoneInput.searchPlaceholder')"
                                class="h-8 pl-8 text-sm placeholder:text-muted-foreground"
                                autocomplete="off"
                                autocorrect="off"
                                spellcheck="false"
                            />
                        </div>
                    </div>
                    <template v-if="isSearchActive">
                        <SelectGroup v-if="filteredRows.length > 0">
                            <SelectItem
                                v-for="opt in filteredRows"
                                :key="opt.code"
                                :value="opt.code"
                            >
                                <span
                                    class="flex w-full min-w-0 items-center gap-2 pr-2"
                                >
                                    <span
                                        class="text-lg leading-none"
                                        aria-hidden="true"
                                        >{{ opt.flag }}</span
                                    >
                                    <span class="min-w-0 flex-1 truncate">{{
                                        opt.label
                                    }}</span>
                                    <span
                                        class="shrink-0 text-xs text-muted-foreground tabular-nums"
                                        >+{{ opt.dial }}</span
                                    >
                                </span>
                            </SelectItem>
                        </SelectGroup>
                        <div
                            v-else
                            class="px-2 py-6 text-center text-sm text-muted-foreground"
                        >
                            {{ t('phoneInput.searchNoResults') }}
                        </div>
                    </template>
                    <template v-else>
                        <SelectGroup>
                            <SelectItem
                                v-for="opt in countryGroups.priorityRows"
                                :key="opt.code"
                                :value="opt.code"
                            >
                                <span
                                    class="flex w-full min-w-0 items-center gap-2 pr-2"
                                >
                                    <span
                                        class="text-lg leading-none"
                                        aria-hidden="true"
                                        >{{ opt.flag }}</span
                                    >
                                    <span class="min-w-0 flex-1 truncate">{{
                                        opt.label
                                    }}</span>
                                    <span
                                        class="shrink-0 text-xs text-muted-foreground tabular-nums"
                                        >+{{ opt.dial }}</span
                                    >
                                </span>
                            </SelectItem>
                        </SelectGroup>
                        <template v-if="countryGroups.restRows.length > 0">
                            <SelectSeparator />
                            <SelectGroup>
                                <SelectLabel>{{
                                    t('phoneInput.prioritySeparator')
                                }}</SelectLabel>
                                <SelectItem
                                    v-for="opt in countryGroups.restRows"
                                    :key="opt.code"
                                    :value="opt.code"
                                >
                                    <span
                                        class="flex w-full min-w-0 items-center gap-2 pr-2"
                                    >
                                        <span
                                            class="text-lg leading-none"
                                            aria-hidden="true"
                                            >{{ opt.flag }}</span
                                        >
                                        <span class="min-w-0 flex-1 truncate">{{
                                            opt.label
                                        }}</span>
                                        <span
                                            class="shrink-0 text-xs text-muted-foreground tabular-nums"
                                            >+{{ opt.dial }}</span
                                        >
                                    </span>
                                </SelectItem>
                            </SelectGroup>
                        </template>
                    </template>
                </SelectContent>
            </Select>
            <div class="relative min-w-0 flex-1">
                <span
                    class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground"
                    >{{ callingCode }}</span
                >
                <Input
                    :id="id"
                    v-model="nationalNumber"
                    type="tel"
                    inputmode="tel"
                    autocomplete="tel-national"
                    class="pl-14"
                    :disabled="disabled"
                    :class="showInvalid ? 'border-destructive' : ''"
                    placeholder="234567890"
                />
            </div>
        </div>
    </div>
</template>
