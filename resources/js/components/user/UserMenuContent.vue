<script setup lang="ts">
import { LogOut, Settings } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRouter } from 'vue-router';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/user/UserInfo.vue';
import { useAuthStore } from '@/stores/auth';
import type { User } from '@/types';

type Props = {
    user: User;
};

defineProps<Props>();

const { t } = useI18n();
const auth = useAuthStore();
const router = useRouter();

async function handleLogout() {
    await auth.logout();
    await router.push({ name: 'login' });
}
</script>

<template>
    <!-- Menu `dir` is set on DropdownMenu root (reka-ui); items use start alignment for RTL. -->
    <DropdownMenuLabel class="p-0 font-normal">
        <div
            class="flex min-w-0 items-center gap-2 px-1 py-1.5 text-start text-sm"
        >
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem as-child>
            <RouterLink
                class="flex w-full cursor-pointer items-center justify-start gap-2 text-start"
                :to="{ name: 'settings.profile' }"
            >
                <Settings class="size-4 shrink-0" />
                <span class="min-w-0 truncate">{{
                    t('userMenu.settings')
                }}</span>
            </RouterLink>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem
        class="w-full cursor-pointer justify-start text-start"
        data-test="logout-button"
        @select="handleLogout"
    >
        <LogOut class="size-4 shrink-0" />
        <span class="min-w-0 truncate">{{ t('userMenu.logout') }}</span>
    </DropdownMenuItem>
</template>
