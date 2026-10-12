import { createInertiaApp } from '@inertiajs/vue3';
import { definePreset } from '@primeuix/themes';
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

const appName = import.meta.env.VITE_APP_NAME || 'ProposalKit';

const ProposalKitPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{cyan.50}',
            100: '{cyan.100}',
            200: '{cyan.200}',
            300: '{cyan.300}',
            400: '{cyan.400}',
            500: '{cyan.500}',
            600: '{cyan.600}',
            700: '{cyan.700}',
            800: '{cyan.800}',
            900: '{cyan.900}',
            950: '{cyan.950}',
        },
        colorScheme: {
            light: {
                primary: {
                    color: '{cyan.600}',
                    inverseColor: '#ffffff',
                    hoverColor: '{cyan.700}',
                    activeColor: '{cyan.800}',
                },
                highlight: {
                    background: '{cyan.50}',
                    focusBackground: '{cyan.100}',
                    color: '{cyan.700}',
                    focusColor: '{cyan.800}',
                },
            },
            dark: {
                primary: {
                    color: '{cyan.300}',
                    inverseColor: '{slate.950}',
                    hoverColor: '{cyan.200}',
                    activeColor: '{cyan.100}',
                },
                highlight: {
                    background: 'rgba(103, 232, 249, 0.16)',
                    focusBackground: 'rgba(103, 232, 249, 0.24)',
                    color: '{cyan.100}',
                    focusColor: '{cyan.50}',
                },
            },
        },
    },
});

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
            case name === 'marketing/Index':
                return null;
            case name.startsWith('errors/'):
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
                    preset: ProposalKitPreset,
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
        color: '#06b6d4',
    },
});
