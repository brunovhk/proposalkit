<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { t } = useTranslations();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('ui.nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
]);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('ui.nav.repository'),
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: t('ui.nav.documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
]);
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-sidebar-border bg-sidebar lg:flex"
    >
        <div class="flex h-16 items-center px-4">
            <Link :href="dashboard()" class="flex items-center gap-2">
                <AppLogo />
            </Link>
        </div>

        <div class="flex-1 overflow-y-auto py-4">
            <NavMain :items="mainNavItems" />
        </div>

        <div class="space-y-3 border-t border-sidebar-border p-3">
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </div>
    </aside>
    <slot />
</template>
