<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/forms/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    processing: boolean;
    errors: Record<string, string[]>;
}>();

const emit = defineEmits<{
    submit: [code: string];
    cancel: [];
}>();

const { t } = useI18n();

const code = ref('');

function onSubmit() {
    emit('submit', code.value);
}

defineExpose({
    clearCode: () => {
        code.value = '';
    },
});
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
        <div class="grid gap-2">
            <Label for="two_factor_code">{{
                t('auth.login.twoFactorCode')
            }}</Label>
            <Input
                id="two_factor_code"
                v-model="code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                autofocus
                maxlength="32"
                :placeholder="t('auth.login.twoFactorCodePlaceholder')"
            />
            <InputError :message="props.errors.code?.[0]" />
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:justify-between">
            <Button
                type="button"
                variant="ghost"
                class="sm:order-1"
                :disabled="props.processing"
                @click="emit('cancel')"
            >
                {{ t('auth.login.backToCredentials') }}
            </Button>
            <Button
                type="submit"
                class="sm:order-2"
                :disabled="props.processing"
            >
                <Spinner v-if="props.processing" />
                {{ t('auth.login.twoFactorSubmit') }}
            </Button>
        </div>
    </form>
</template>
