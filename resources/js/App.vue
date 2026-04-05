<script setup lang="ts">
import 'vue-sonner/style.css';
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterView } from 'vue-router';
import { Toaster } from '@/components/ui/sonner';
import type { LocaleCode } from '@/i18n/locale-storage';
import { persistLocale } from '@/i18n/locale-storage';

const { locale } = useI18n();

watch(
    locale,
    (code) => {
        document.documentElement.setAttribute('lang', code);
        document.documentElement.setAttribute(
            'dir',
            code === 'ar' ? 'rtl' : 'ltr',
        );
        persistLocale(code as LocaleCode);
    },
    { immediate: true },
);
</script>

<template>
    <RouterView />
    <Toaster position="top-center" :rich-colors="true" />
</template>
