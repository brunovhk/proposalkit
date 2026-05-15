<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { computed, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

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
    <Toast />
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
