import { createPinia } from 'pinia';
import { createApp } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { i18n } from '@/i18n';
import { useAuthStore } from '@/stores/auth';
import App from './App.vue';
import router from './router';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

document.title = appName;

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
useAuthStore(pinia).hydrateFromStorage();
app.use(i18n);
app.use(router);
app.mount('#app');

initializeTheme();
