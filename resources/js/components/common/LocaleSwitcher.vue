<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { LocaleCode } from '@/i18n/locale-storage';
import { SUPPORTED_LOCALES } from '@/i18n/locale-storage';

withDefaults(
    defineProps<{
        /** When true, shows a text label above the select (e.g. on dense forms). */
        showLabel?: boolean;
    }>(),
    {
        showLabel: false,
    },
);

const { locale, t } = useI18n();

function onLocaleChange(value: unknown): void {
    if (value === null || value === undefined || value === '') {
        return;
    }

    const code = String(value);

    if (!SUPPORTED_LOCALES.includes(code as LocaleCode)) {
        return;
    }

    locale.value = code as LocaleCode;
}
</script>

<template>
    <div :class="showLabel ? 'flex flex-col gap-2' : undefined">
        <span
            v-if="showLabel"
            class="text-sm font-medium text-muted-foreground"
            >{{ t('locale.label') }}</span
        >
        <Select :model-value="locale" @update:model-value="onLocaleChange">
            <SelectTrigger class="w-full max-w-xs">
                <SelectValue :placeholder="t('locale.label')" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="en">{{ t('locale.en') }}</SelectItem>
                <SelectItem value="vi">{{ t('locale.vi') }}</SelectItem>
                <SelectItem value="ar">{{ t('locale.ar') }}</SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
