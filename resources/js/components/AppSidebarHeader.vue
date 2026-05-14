<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, Menu } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { NavItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { t } = useTranslations();
const visible = ref(false);

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
    <header
        class="flex h-16 shrink-0 items-center gap-3 border-b border-surface-200/70 px-4 lg:px-6 dark:border-surface-700/70"
    >
        <Button
            type="button"
            text
            rounded
            class="lg:hidden"
            :aria-label="t('ui.nav.navigation_menu')"
            @click="visible = true"
        >
            <Menu class="size-5" />
        </Button>

        <template v-if="breadcrumbs && breadcrumbs.length > 0">
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </template>

        <Drawer
            v-model:visible="visible"
            position="left"
            :header="t('ui.nav.navigation_menu')"
            class="w-80"
        >
            <div class="flex h-full flex-col">
                <Link :href="dashboard()" class="mb-6 flex items-center gap-2">
                    <AppLogoIcon
                        class="size-8 fill-current text-black dark:text-white"
                    />
                </Link>
                <div class="flex-1">
                    <NavMain :items="mainNavItems" />
                </div>
                <NavFooter :items="footerNavItems" />
                <NavUser />
            </div>
        </Drawer>
    </header>
</template>
