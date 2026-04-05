<script setup lang="ts">
import { useDropZone, useFileDialog } from '@vueuse/core';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { computed, nextTick, onUnmounted, ref, useAttrs, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import { http } from '@/api/http';
import { Avatar } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useInitials } from '@/composables/useInitials';
import type { CommitPendingAvatarResult } from '@/lib/avatar-commit';
import { extractAvatarFromApiResponseBody } from '@/lib/extract-api-avatar';
import { resolvePublicImageUrl } from '@/lib/media-url';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        /** Stored avatar URL (https). */
        modelValue: string;
        id: string;
        placeholder?: string;
        disabled?: boolean;
        invalid?: boolean;
        fallbackName?: string;
        size?: 'md' | 'lg';
        /** POST `/api/v1/me/avatar` (requires auth). */
        enableUpload?: boolean;
        /**
         * When true with enableUpload, picked files are only sent when the parent
         * calls commitPendingAvatarUpload() (e.g. on form submit).
         */
        deferUpload?: boolean;
        /**
         * default — avatar left; optional drop zone on the right when enableUpload && showDropZone.
         * profile — avatar left, #beside-avatar slot right; optional drop zone below when both flags set.
         */
        layout?: 'default' | 'profile';
        /** Dashed drag-and-drop target; pick file via pencil button when false. */
        showDropZone?: boolean;
    }>(),
    {
        placeholder: '',
        disabled: false,
        invalid: false,
        fallbackName: '',
        size: 'lg',
        enableUpload: false,
        deferUpload: true,
        layout: 'default',
        showDropZone: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
    uploaded: [];
}>();

const attrs = useAttrs();
/** Avoid `|` in templates (vue/no-deprecated-filter parses it as a Vue 2 filter). */
const rootAttrsClass = computed(() => {
    const c = attrs.class;

    return typeof c === 'string' ? c : undefined;
});
const { t } = useI18n();
const { getInitials } = useInitials();

const dropZoneRef = ref<HTMLElement | null>(null);
const blobPreviewUrl = ref<string | null>(null);
/** File waiting for commitPendingAvatarUpload when deferUpload is true. */
const pendingFile = ref<File | null>(null);
const uploading = ref(false);
const uploadProgress = ref(0);
const previewImageFailed = ref(false);

const ACCEPT = 'image/jpeg,image/png,image/webp,image/gif';
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const MAX_BYTES = 2 * 1024 * 1024;

const displaySrc = computed(() => {
    if (blobPreviewUrl.value) {
        return blobPreviewUrl.value;
    }

    return resolvePublicImageUrl(props.modelValue);
});

const initials = computed(() => {
    const i = getInitials(props.fallbackName?.trim() || undefined);

    return i || '?';
});

const avatarSizeClass = computed(() =>
    props.size === 'lg'
        ? 'size-20 ring-2 ring-border sm:size-24'
        : 'size-16 ring-2 ring-border sm:size-20',
);

const showRemove = computed(
    () =>
        !props.disabled &&
        (props.modelValue.trim() !== '' ||
            pendingFile.value !== null ||
            blobPreviewUrl.value !== null),
);

function revokeBlob(): void {
    if (blobPreviewUrl.value) {
        URL.revokeObjectURL(blobPreviewUrl.value);
        blobPreviewUrl.value = null;
    }
}

watch(displaySrc, () => {
    previewImageFailed.value = false;
});

function onPreviewImgError(src: string): void {
    previewImageFailed.value = true;

    if (import.meta.env.DEV) {
        console.warn('[AvatarUpload] preview image failed to load', { src });
    }
}

/** New server URL after defer upload must not keep a stale @error flag from a previous src. */
watch(
    () => props.modelValue,
    (next, prev) => {
        if (next !== prev) {
            previewImageFailed.value = false;
        }

        if (import.meta.env.DEV && next?.trim()) {
            console.debug('[AvatarUpload] modelValue → display', {
                raw: next,
                resolved: resolvePublicImageUrl(next),
            });
        }
    },
);

onUnmounted(() => {
    revokeBlob();
});

const { isOverDropZone } = useDropZone(dropZoneRef, {
    multiple: false,
    dataTypes: ALLOWED_TYPES,
    onDrop: (files) => {
        const f = files?.[0];

        if (f) {
            void processFile(f);
        }
    },
});

const { open, onChange } = useFileDialog({
    accept: ACCEPT,
    multiple: false,
});

onChange((files) => {
    const f = files?.[0];

    if (f) {
        void processFile(f);
    }
});

function validateFile(file: File): string | null {
    if (!ALLOWED_TYPES.includes(file.type)) {
        return t('profile.avatarInvalidType');
    }

    if (file.size > MAX_BYTES) {
        return t('profile.avatarInvalidSize');
    }

    return null;
}

async function processFile(file: File): Promise<void> {
    if (!props.enableUpload || props.disabled) {
        return;
    }

    const err = validateFile(file);

    if (err) {
        toast.error(err);

        return;
    }

    revokeBlob();
    pendingFile.value = null;
    blobPreviewUrl.value = URL.createObjectURL(file);

    if (props.deferUpload) {
        pendingFile.value = file;

        return;
    }

    const url = await uploadFileToServer(file);
    await nextTick();
    revokeBlob();

    if (!url) {
        pendingFile.value = null;
    }
}

function removeAvatar(): void {
    pendingFile.value = null;
    revokeBlob();
    emit('update:modelValue', '');
}

function openPicker(): void {
    if (props.disabled || uploading.value || !props.enableUpload) {
        return;
    }

    open();
}

/**
 * Upload a pending file (deferUpload). Call from the parent before saving the form.
 */
async function commitPendingAvatarUpload(): Promise<CommitPendingAvatarResult> {
    if (!props.enableUpload || !props.deferUpload) {
        return { ok: true, pendingUploaded: false };
    }

    const file = pendingFile.value;

    if (!file) {
        return { ok: true, pendingUploaded: false };
    }

    const url = await uploadFileToServer(file);

    if (!url) {
        return { ok: false };
    }

    pendingFile.value = null;
    await nextTick();
    revokeBlob();
    previewImageFailed.value = false;

    return { ok: true, pendingUploaded: true, avatarUrl: url };
}

async function uploadFileToServer(file: File): Promise<string | null> {
    uploading.value = true;
    uploadProgress.value = 0;

    try {
        const form = new FormData();
        form.append('avatar', file);

        const res = await http.post<unknown>('/me/avatar', form, {
            onUploadProgress: (e) => {
                const total = e.total ?? 0;

                if (total > 0) {
                    uploadProgress.value = Math.round((e.loaded / total) * 100);
                }
            },
        });

        const url = extractAvatarFromApiResponseBody(res.data);

        if (!url) {
            toast.error(t('profile.avatarUploadFailed'));

            return null;
        }

        emit('update:modelValue', url);
        emit('uploaded');
        toast.success(t('profile.avatarUploadSuccess'));

        return url;
    } catch {
        toast.error(t('profile.avatarUploadFailed'));

        return null;
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
}

defineExpose({
    commitPendingAvatarUpload,
});
</script>

<template>
    <!-- Profile: avatar | slot; drop zone full width below when enableUpload -->
    <div
        v-if="layout === 'profile'"
        class="flex flex-col gap-4"
        :class="rootAttrsClass"
        v-bind="{ ...attrs, class: undefined }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-4">
            <div :id="id" class="relative shrink-0">
                <Avatar
                    :class="
                        cn(
                            'overflow-hidden rounded-full',
                            avatarSizeClass,
                            invalid
                                ? 'ring-2 ring-destructive ring-offset-2 ring-offset-background'
                                : '',
                        )
                    "
                >
                    <img
                        v-if="displaySrc && !previewImageFailed"
                        :key="displaySrc"
                        :src="displaySrc"
                        alt=""
                        class="absolute inset-0 z-10 size-full object-cover"
                        @error="onPreviewImgError(displaySrc)"
                    />
                    <span
                        v-else
                        class="flex size-full items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground"
                    >
                        {{ initials }}
                    </span>
                </Avatar>

                <Button
                    v-if="enableUpload && showRemove"
                    type="button"
                    size="icon"
                    variant="outline"
                    class="absolute start-0 bottom-0 z-20 size-8 rounded-full shadow-sm"
                    :disabled="disabled || uploading"
                    :aria-label="t('profile.avatarRemove')"
                    @click="removeAvatar"
                >
                    <Trash2 class="size-3.5" />
                </Button>

                <Button
                    v-if="enableUpload"
                    type="button"
                    size="icon"
                    variant="outline"
                    class="absolute end-0 bottom-0 z-20 size-8 rounded-full shadow-sm"
                    :disabled="disabled || uploading"
                    :aria-label="t('profile.avatarUploadChoose')"
                    @click="openPicker"
                >
                    <Spinner v-if="uploading" class="size-4" />
                    <Pencil v-else class="size-3.5" />
                </Button>
            </div>

            <div class="min-w-0 flex-1">
                <slot name="beside-avatar" />
            </div>
        </div>

        <div
            v-if="enableUpload && showDropZone"
            ref="dropZoneRef"
            role="button"
            tabindex="0"
            class="w-full cursor-pointer rounded-lg border border-dashed border-input p-4 text-center transition-colors hover:bg-accent/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            :class="
                isOverDropZone ? 'border-primary bg-primary/5' : 'bg-muted/30'
            "
            @click="openPicker"
            @keydown.enter.prevent="openPicker"
            @keydown.space.prevent="openPicker"
        >
            <template v-if="!uploading">
                <p class="text-sm font-medium">
                    {{ t('profile.avatarDropTitle') }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('profile.avatarDropPick') }}
                </p>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{ t('profile.avatarDropFormats') }}
                </p>
            </template>
            <div v-else class="space-y-2">
                <p class="text-sm text-muted-foreground">
                    {{ t('profile.avatarUploading') }}
                    <span class="tabular-nums">{{ uploadProgress }}%</span>
                </p>
                <div
                    class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    :aria-valuenow="uploadProgress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-[width] duration-200"
                        :style="{ width: `${uploadProgress}%` }"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Default: avatar + optional drop zone -->
    <div
        v-else
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4"
        :class="rootAttrsClass"
        v-bind="{ ...attrs, class: undefined }"
    >
        <div :id="id" class="relative shrink-0">
            <Avatar
                :class="
                    cn(
                        'overflow-hidden rounded-full',
                        avatarSizeClass,
                        invalid
                            ? 'ring-2 ring-destructive ring-offset-2 ring-offset-background'
                            : '',
                    )
                "
            >
                <img
                    v-if="displaySrc && !previewImageFailed"
                    :key="displaySrc"
                    :src="displaySrc"
                    alt=""
                    class="absolute inset-0 z-10 size-full object-cover"
                    @error="onPreviewImgError(displaySrc)"
                />
                <span
                    v-else
                    class="flex size-full items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground"
                >
                    {{ initials }}
                </span>
            </Avatar>

            <Button
                v-if="enableUpload && showRemove"
                type="button"
                size="icon"
                variant="outline"
                class="absolute start-0 bottom-0 z-20 size-8 rounded-full shadow-sm"
                :disabled="disabled || uploading"
                :aria-label="t('profile.avatarRemove')"
                @click="removeAvatar"
            >
                <Trash2 class="size-3.5" />
            </Button>

            <Button
                v-if="enableUpload"
                type="button"
                size="icon"
                variant="outline"
                class="absolute end-0 bottom-0 z-20 size-8 rounded-full shadow-sm"
                :disabled="disabled || uploading"
                :aria-label="t('profile.avatarUploadChoose')"
                @click="openPicker"
            >
                <Spinner v-if="uploading" class="size-4" />
                <Pencil v-else class="size-3.5" />
            </Button>
        </div>

        <div
            v-if="enableUpload && showDropZone"
            ref="dropZoneRef"
            role="button"
            tabindex="0"
            class="min-w-0 flex-1 cursor-pointer rounded-lg border border-dashed border-input p-4 text-center transition-colors hover:bg-accent/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            :class="
                isOverDropZone ? 'border-primary bg-primary/5' : 'bg-muted/30'
            "
            @click="openPicker"
            @keydown.enter.prevent="openPicker"
            @keydown.space.prevent="openPicker"
        >
            <template v-if="!uploading">
                <p class="text-sm font-medium">
                    {{ t('profile.avatarDropTitle') }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('profile.avatarDropPick') }}
                </p>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{ t('profile.avatarDropFormats') }}
                </p>
            </template>
            <div v-else class="space-y-2">
                <p class="text-sm text-muted-foreground">
                    {{ t('profile.avatarUploading') }}
                    <span class="tabular-nums">{{ uploadProgress }}%</span>
                </p>
                <div
                    class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    :aria-valuenow="uploadProgress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-[width] duration-200"
                        :style="{ width: `${uploadProgress}%` }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
