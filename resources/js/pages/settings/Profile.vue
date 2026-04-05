<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { http } from '@/api';
import Heading from '@/components/common/Heading.vue';
import AvatarUpload from '@/components/forms/AvatarUpload.vue';
import InputError from '@/components/forms/InputError.vue';
import PhoneInput from '@/components/forms/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import DeleteUser from '@/components/user/DeleteUser.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { CommitPendingAvatarResult } from '@/lib/avatar-commit';
import { useAuthStore } from '@/stores/auth';
import { useAuthConfigStore } from '@/stores/authConfig';

const { t } = useI18n();
const auth = useAuthStore();

const name = ref('');
const email = ref('');
const avatar = ref('');
const phoneE164 = ref('');
const defaultPhoneRegion = ref('VN');
const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const recentlySuccessful = ref(false);
const resendStatus = ref('');
const avatarUploadRef = ref<{
    commitPendingAvatarUpload: () => Promise<CommitPendingAvatarResult>;
} | null>(null);

watch(
    () => auth.user,
    (u) => {
        if (u) {
            name.value = u.name;
            email.value = u.email;
            avatar.value = u.avatar ?? '';
            phoneE164.value = u.phone ?? '';
        }
    },
    { immediate: true },
);

const mustVerify = computed(() => !!auth.user && !auth.user.email_verified_at);

const breadcrumbs = computed(() => [
    {
        title: t('settings.breadcrumbSettings'),
        href: { name: 'settings.profile' } as const,
    },
    {
        title: t('settings.profile'),
        href: { name: 'settings.profile' } as const,
    },
]);

const authConfigStore = useAuthConfigStore();

void authConfigStore.ensureConfig().then(() => {
    defaultPhoneRegion.value = authConfigStore.defaultPhoneRegion;
});

async function submitProfile() {
    processing.value = true;
    errors.value = {};
    recentlySuccessful.value = false;

    try {
        const avatarCmp = avatarUploadRef.value;
        let commit: CommitPendingAvatarResult = {
            ok: true,
            pendingUploaded: false,
        };

        if (avatarCmp) {
            commit = await avatarCmp.commitPendingAvatarUpload();

            if (!commit.ok) {
                return;
            }

            if (commit.pendingUploaded) {
                avatar.value = commit.avatarUrl;
            }
        }

        await http.patch('/me', {
            name: name.value,
            email: email.value,
            phone: phoneE164.value.trim() || null,
            ...(commit.pendingUploaded
                ? {}
                : { avatar: avatar.value.trim() || null }),
        });
        await auth.fetchUser();

        // After upload + PATCH, GET /me should include the new avatar. If it is missing
        // (serialization, proxy, or timing), keep the URL from the upload response so the preview stays valid.
        if (commit.pendingUploaded && commit.avatarUrl) {
            const apiAvatar = auth.user?.avatar?.trim() ?? '';

            if (import.meta.env.DEV) {
                console.debug('[Profile] avatar after save', {
                    apiAvatar,
                    committedUrl: commit.avatarUrl,
                });
            }

            if (!apiAvatar) {
                console.warn(
                    '[Profile] GET /me returned empty avatar after upload; using URL from POST /me/avatar',
                );
                avatar.value = commit.avatarUrl;
            }
        }

        recentlySuccessful.value = true;
        setTimeout(() => {
            recentlySuccessful.value = false;
        }, 2500);
    } catch (e: unknown) {
        const err = e as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };

        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
    } finally {
        processing.value = false;
    }
}

async function resendVerification() {
    resendStatus.value = '';

    try {
        const { data } = await http.post('/auth/email/resend');
        resendStatus.value = data.message ?? '';
    } catch {
        resendStatus.value = '';
    }
}
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout>
            <h1 class="sr-only">{{ t('profile.pageTitle') }}</h1>

            <div class="flex flex-col gap-6">
                <Heading
                    variant="small"
                    :title="t('profile.headingTitle')"
                    :description="t('profile.headingDescription')"
                />

                <form
                    class="flex flex-col gap-6"
                    @submit.prevent="submitProfile"
                >
                    <div class="grid gap-3">
                        <AvatarUpload
                            ref="avatarUploadRef"
                            id="avatar"
                            v-model="avatar"
                            class="block w-full"
                            layout="profile"
                            enable-upload
                            :fallback-name="name"
                            :invalid="!!errors.avatar?.[0]"
                        >
                            <template #beside-avatar>
                                <div class="flex flex-col gap-4">
                                    <div class="grid gap-2">
                                        <Input
                                            id="name"
                                            v-model="name"
                                            class="block w-full"
                                            required
                                            autocomplete="name"
                                            :aria-label="t('profile.name')"
                                            :placeholder="
                                                t(
                                                    'auth.register.namePlaceholder',
                                                )
                                            "
                                        />
                                        <InputError
                                            class="mt-1"
                                            :message="errors.name?.[0]"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Input
                                            id="email"
                                            v-model="email"
                                            type="email"
                                            class="block w-full"
                                            required
                                            autocomplete="username"
                                            :aria-label="t('profile.email')"
                                            :placeholder="
                                                t('profile.emailPlaceholder')
                                            "
                                        />
                                        <InputError
                                            class="mt-1"
                                            :message="errors.email?.[0]"
                                        />
                                    </div>
                                </div>
                            </template>
                        </AvatarUpload>
                        <InputError
                            class="mt-1"
                            :message="errors.avatar?.[0]"
                        />
                    </div>

                    <PhoneInput
                        id="profile-phone"
                        v-model="phoneE164"
                        :default-region="defaultPhoneRegion"
                        :label="t('profile.phone')"
                        :invalid="!!errors.phone?.[0]"
                    />
                    <InputError class="mt-2" :message="errors.phone?.[0]" />

                    <div v-if="mustVerify">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            {{ t('profile.unverifiedIntro') }}
                            <button
                                type="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500"
                                @click="resendVerification"
                            >
                                {{ t('profile.resendCta') }}
                            </button>
                        </p>

                        <div
                            v-if="resendStatus"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            {{ resendStatus }}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="update-profile-button"
                        >
                            <Spinner v-if="processing" />
                            {{ t('profile.save') }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                {{ t('profile.saved') }}
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser v-if="auth.user?.email_verified_at" />
        </SettingsLayout>
    </AppSidebarLayout>
</template>
