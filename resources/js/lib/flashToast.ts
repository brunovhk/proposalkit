import { router } from '@inertiajs/vue3';
import type { ToastServiceMethods } from 'primevue/toastservice';
import type { FlashToast } from '@/types/ui';

const severity = {
    success: 'success',
    info: 'info',
    warning: 'warn',
    error: 'error',
} as const;

export function initializeFlashToast(toast: ToastServiceMethods): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        toast.add({
            severity: severity[data.type],
            summary: data.message,
            life: 4000,
        });
    });
}
