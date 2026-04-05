import { createRouter, createWebHistory } from 'vue-router';
import { dashboardAppPath } from '@/config/dashboardPrefix';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: () => import('@/pages/Welcome.vue'),
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/pages/auth/Login.vue'),
            meta: { guest: true },
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/pages/auth/Register.vue'),
            meta: { guest: true },
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: () => import('@/pages/auth/ForgotPassword.vue'),
            meta: { guest: true },
        },
        {
            path: '/reset-password',
            name: 'reset-password',
            component: () => import('@/pages/auth/ResetPassword.vue'),
            meta: { guest: true },
        },
        {
            path: '/verify-email',
            name: 'verify-email',
            component: () => import('@/pages/auth/VerifyEmail.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: dashboardAppPath('dashboard'),
            name: 'dashboard',
            component: () => import('@/pages/Dashboard.vue'),
            meta: { requiresAuth: true, requiresVerified: true },
        },
        {
            path: dashboardAppPath('settings/profile'),
            name: 'settings.profile',
            component: () => import('@/pages/settings/Profile.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: dashboardAppPath('settings/security'),
            name: 'settings.security',
            component: () => import('@/pages/settings/Security.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: dashboardAppPath('settings/appearance'),
            name: 'settings.appearance',
            component: () => import('@/pages/settings/Appearance.vue'),
            meta: { requiresAuth: true },
        },
    ],
});

router.beforeEach(async (to, _from, next) => {
    const auth = useAuthStore();

    if (auth.isAuthenticated && !auth.user) {
        try {
            await auth.fetchUser();
        } catch {
            auth.clearSession();
        }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } });

        return;
    }

    if (to.meta.guest && auth.isAuthenticated) {
        next(
            auth.user?.email_verified_at
                ? { name: 'dashboard' }
                : { name: 'verify-email' },
        );

        return;
    }

    if (to.meta.requiresVerified && auth.user && !auth.user.email_verified_at) {
        next({ name: 'verify-email' });

        return;
    }

    next();
});

export default router;
