<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps({
    status: {
        type: Number,
        required: true,
    },
});

const { t } = useTranslations();

const errorPages: Record<
    number,
    {
        titleKey: string;
        descriptionKey: string;
    }
> = {
    403: {
        titleKey: 'ui.errors.403.title',
        descriptionKey: 'ui.errors.403.body',
    },
    404: {
        titleKey: 'ui.errors.404.title',
        descriptionKey: 'ui.errors.404.body',
    },
    405: {
        titleKey: 'ui.errors.405.title',
        descriptionKey: 'ui.errors.405.body',
    },
    410: {
        titleKey: 'ui.errors.410.title',
        descriptionKey: 'ui.errors.410.body',
    },
    500: {
        titleKey: 'ui.errors.500.title',
        descriptionKey: 'ui.errors.500.body',
    },
    503: {
        titleKey: 'ui.errors.503.title',
        descriptionKey: 'ui.errors.503.body',
    },
};

const errorPage = computed(() => errorPages[props.status] ?? errorPages[500]);

const title = computed(() => t(errorPage.value.titleKey));
const description = computed(() => t(errorPage.value.descriptionKey));
</script>

<template>
    <Head :title="title" />

    <main
        class="flex min-h-screen items-center justify-center bg-surface-50 px-6 py-12 font-sans text-surface-900 antialiased transition-colors dark:bg-surface-950 dark:text-surface-0"
    >
        <section
            class="w-full max-w-xl rounded-md border border-surface-200 bg-surface-0 p-8 text-center shadow-sm transition-colors dark:border-surface-800 dark:bg-surface-900"
        >
            <p class="text-sm font-semibold text-cyan-600 dark:text-cyan-300">
                {{ status }}
            </p>

            <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                {{ title }}
            </h1>

            <p
                class="mt-4 text-base leading-7 text-surface-600 dark:text-surface-300"
            >
                {{ description }}
            </p>

            <Link
                href="/"
                class="mt-8 inline-flex items-center justify-center rounded-md bg-surface-950 px-4 py-2 text-sm font-medium text-surface-0 transition hover:bg-surface-800 focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 focus-visible:ring-offset-surface-50 focus-visible:outline-none dark:bg-surface-0 dark:text-surface-950 dark:hover:bg-surface-200 dark:focus-visible:ring-cyan-300 dark:focus-visible:ring-offset-surface-950"
            >
                {{ t('ui.errors.return_home') }}
            </Link>
        </section>
    </main>
</template>
