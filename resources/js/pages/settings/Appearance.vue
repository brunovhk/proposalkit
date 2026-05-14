<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/appearance';

const { t } = useTranslations();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.settings.appearance.breadcrumb'),
                href: edit(),
            },
        ],
    });
});
</script>

<template>
    <Head :title="t('ui.settings.appearance.head')" />

    <h1 class="sr-only">{{ t('ui.settings.appearance.head') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('ui.settings.appearance.title')"
            :description="t('ui.settings.appearance.description')"
        />
        <AppearanceTabs />
    </div>
</template>
