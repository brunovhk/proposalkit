import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

type FlashType = 'success' | 'warning' | 'error';

type FlashProps = Partial<Record<FlashType, string | null>>;

const toastSeverity = {
    success: 'success',
    warning: 'warn',
    error: 'error',
} as const;

const toastSummary = {
    success: 'ui.messages.success_title',
    warning: 'ui.messages.warning_title',
    error: 'ui.messages.error_title',
} as const;

const recentlyFlashed = new Set<string>();

export function useFlashToasts(): void {
    const page = usePage();
    const toast = useToast();
    const { t } = useTranslations();

    const flashMessages = computed<Record<FlashType, string | null>>(() => {
        const flash = page.props.flash as FlashProps | undefined;

        return {
            success: flash?.success ?? null,
            warning: flash?.warning ?? null,
            error: flash?.error ?? null,
        };
    });

    watch(
        flashMessages,
        (messages, previousMessages) => {
            Object.entries(messages).forEach(([type, message]) => {
                const flashType = type as FlashType;

                if (!message || message === previousMessages?.[flashType]) {
                    return;
                }

                const signature = `${flashType}:${message}`;

                if (recentlyFlashed.has(signature)) {
                    clearFlash(flashType);

                    return;
                }

                recentlyFlashed.add(signature);

                toast.add({
                    severity: toastSeverity[flashType],
                    summary: t(toastSummary[flashType]),
                    detail: message,
                    life: flashType === 'warning' ? 5000 : 3000,
                });

                clearFlash(flashType);

                window.setTimeout(() => {
                    recentlyFlashed.delete(signature);
                }, 1000);
            });
        },
        { immediate: true },
    );

    function clearFlash(type: FlashType): void {
        const flash = page.props.flash as FlashProps | undefined;

        if (flash) {
            flash[type] = null;
        }
    }
}
