<script setup lang="ts">
import { ref, useTemplateRef, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { http } from '@/api';
import Heading from '@/components/common/Heading.vue';
import InputError from '@/components/forms/InputError.vue';
import PasswordInput from '@/components/forms/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useAuthStore } from '@/stores/auth';

const { t } = useI18n();
const router = useRouter();
const auth = useAuthStore();

const password = ref('');
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const open = ref(false);
const passwordInput = useTemplateRef('passwordInput');

async function submitDelete() {
    processing.value = true;
    errors.value = {};

    try {
        await http.delete('/me', { data: { password: password.value } });
        auth.clearSession();
        open.value = false;
        await router.push({ name: 'login' });
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }

        passwordInput.value?.focus?.();
    } finally {
        processing.value = false;
    }
}

watch(open, (isOpen) => {
    if (!isOpen) {
        password.value = '';
        errors.value = {};
    }
});
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('deleteUser.title')"
            :description="t('deleteUser.description')"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ t('deleteUser.warningTitle') }}</p>
                <p class="text-sm">
                    {{ t('deleteUser.warningBody') }}
                </p>
            </div>
            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                    >
                        {{ t('deleteUser.openButton') }}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit.prevent="submitDelete">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                {{ t('deleteUser.dialogTitle') }}
                            </DialogTitle>
                            <DialogDescription>
                                {{ t('deleteUser.dialogDescription') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="delete-password" class="sr-only">
                                {{ t('auth.login.password') }}
                            </Label>
                            <PasswordInput
                                id="delete-password"
                                ref="passwordInput"
                                v-model="password"
                                :placeholder="
                                    t('deleteUser.passwordPlaceholder')
                                "
                            />
                            <InputError :message="errors.password?.[0]" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="secondary">
                                    {{ t('deleteUser.cancel') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ t('deleteUser.confirm') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
