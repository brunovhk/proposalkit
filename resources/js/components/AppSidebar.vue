<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { useNavigation } from '@/composables/useNavigation';
import { dashboard } from '@/routes';

const { mainNavItems } = useNavigation();
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-surface-200 bg-surface-0 lg:flex dark:border-surface-700 dark:bg-surface-900"
    >
        <div class="flex h-16 items-center px-4">
            <Link :href="dashboard()" class="flex items-center gap-2">
                <AppLogo />
            </Link>
        </div>

        <div class="flex-1 overflow-y-auto py-4">
            <NavMain :items="mainNavItems" />
        </div>

        <div
            class="space-y-3 border-t border-surface-200 p-3 dark:border-surface-700"
        >
            <NavUser />
        </div>
    </aside>
    <slot />
</template>
