<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { MenuItem } from 'primevue/menuitem';
import { computed } from 'vue';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

const props = defineProps<Props>();

const items = computed<MenuItem[]>(() =>
    props.breadcrumbs.map((item, index) => ({
        label: item.title,
        route: item.href,
        current: index === props.breadcrumbs.length - 1,
    })),
);
</script>

<template>
    <Breadcrumb :model="items">
        <template #item="{ item, props: itemProps }">
            <span v-if="item.current" v-bind="itemProps.action">
                {{ item.label }}
            </span>
            <Link v-else :href="item.route" v-bind="itemProps.action">
                {{ item.label }}
            </Link>
        </template>
    </Breadcrumb>
</template>
