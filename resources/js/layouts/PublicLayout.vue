<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

type FlashProps = {
    success?: string | null;
};

const page = usePage();
const toast = useToast();
const { t } = useTranslations();

const flashSuccess = computed(() => {
    const flash = page.props.flash as FlashProps | undefined;

    return flash?.success ?? null;
});

watch(
    flashSuccess,
    (newSuccess) => {
        if (!newSuccess) {
            return;
        }

        toast.add({
            severity: 'success',
            summary: t('ui.messages.success_title'),
            detail: newSuccess,
            life: 3000,
        });

        clearSuccessFlash();
    },
    { immediate: true },
);

function clearSuccessFlash(): void {
    const flash = page.props.flash as FlashProps | undefined;

    if (flash) {
        flash.success = null;
    }
}
</script>

<template>
    <Toast position="top-right" />
    <main class="min-h-screen bg-surface-50 px-4 py-8 dark:bg-surface-950">
        <div class="mx-auto max-w-5xl">
            <slot />
        </div>
    </main>
</template>
