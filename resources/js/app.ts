import { createInertiaApp } from '@inertiajs/vue3';
import Aura from '@primeuix/themes/aura';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import PrimeVue from 'primevue/config';
import Ripple from 'primevue/ripple';
import ToastService from 'primevue/toastservice';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
// PrimeVue

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

initializeTheme();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name === 'proposals/PublicView':
                return PublicLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    // PrimeVue
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                ripple: true,
                theme: {
                    preset: Aura,
                    options: {
                        cssLayer: {
                            name: 'primevue',
                            order: 'theme, base, primevue',
                        },
                        darkModeSelector: '.dark',
                    },
                },
            })
            .use(ToastService)
            .directive('ripple', Ripple);

        initializeFlashToast(vueApp.config.globalProperties.$toast);

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
