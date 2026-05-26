<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Monitor, Moon, Sun } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTranslations } from '@/composables/useTranslations';
import { edit } from '@/routes/appearance';
import type { Appearance } from '@/types';

const { t } = useTranslations();
const { appearance, updateAppearance } = useAppearance();

type AppearanceOption = {
    label: string;
    value: Appearance;
    icon?: LucideIcon;
};

const appearanceOptions = computed<AppearanceOption[]>(() => [
    {
        label: t('ui.settings.appearance.light'),
        value: 'light',
        icon: Sun,
    },
    {
        label: t('ui.settings.appearance.dark'),
        value: 'dark',
        icon: Moon,
    },
    {
        label: t('ui.settings.appearance.system'),
        value: 'system',
        icon: Monitor,
    },
]);

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
        <SelectButton
            :model-value="appearance"
            :options="appearanceOptions"
            option-label="label"
            option-value="value"
            @update:model-value="
                (value) => updateAppearance(value as Appearance)
            "
        >
            <template #option="{ option }">
                <span class="flex items-center gap-2">
                    <component
                        :is="option.icon"
                        class="h-4 w-4"
                        aria-hidden="true"
                    />
                    <span>{{ option.label }}</span>
                </span>
            </template>
        </SelectButton>
    </div>
</template>
