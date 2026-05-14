<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
const { t } = useTranslations();
</script>

<template>
    <nav class="space-y-1 px-2" :aria-label="t('ui.nav.platform')">
        <p
            class="px-3 pb-2 text-xs font-medium text-surface-500 uppercase dark:text-surface-400"
        >
            {{ t('ui.nav.platform') }}
        </p>
        <Link
            v-for="item in items"
            :key="item.title"
            :href="item.href"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-surface-500 transition-colors hover:bg-surface-200 hover:text-surface-900 dark:text-surface-400 dark:hover:bg-surface-700 dark:hover:text-surface-0"
            :class="{
                'bg-surface-100 text-surface-900 dark:bg-surface-800 dark:text-surface-0':
                    isCurrentUrl(item.href),
            }"
        >
            <component v-if="item.icon" :is="item.icon" class="size-4" />
            <span>{{ item.title }}</span>
        </Link>
    </nav>
</template>
