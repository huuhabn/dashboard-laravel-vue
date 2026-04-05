<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { resolvePublicImageUrl } from '@/lib/media-url';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

const avatarSrc = computed(() => resolvePublicImageUrl(props.user.avatar));

const showAvatar = computed(() => avatarSrc.value !== '');
</script>

<template>
    <Avatar class="h-8 w-8 shrink-0 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="avatarSrc" :alt="user.name" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid min-w-0 flex-1 text-start text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>
