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
            class="px-3 pb-2 text-xs font-medium text-muted-foreground uppercase"
        >
            {{ t('ui.nav.platform') }}
        </p>
        <Link
            v-for="item in items"
            :key="item.title"
            :href="item.href"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            :class="{
                'bg-muted text-foreground': isCurrentUrl(item.href),
            }"
        >
            <component v-if="item.icon" :is="item.icon" class="size-4" />
            <span>{{ item.title }}</span>
        </Link>
    </nav>
</template>
