<script setup lang="ts">
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

type Props = {
    items: NavItem[];
    class?: string;
};

defineProps<Props>();
</script>

<template>
    <nav class="space-y-1 px-2" :class="$props.class">
        <a
            v-for="item in items"
            :key="item.title"
            :href="toUrl(item.href)"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-surface-500 transition-colors hover:bg-surface-200 hover:text-surface-900 dark:text-surface-400 dark:hover:bg-surface-700 dark:hover:text-surface-0"
        >
            <component v-if="item.icon" :is="item.icon" class="size-4" />
            <span>{{ item.title }}</span>
        </a>
    </nav>
</template>
