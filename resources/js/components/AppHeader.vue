<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    Menu as MenuIcon,
    Search,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();
const { t } = useTranslations();
const mobileMenuVisible = ref(false);
const userMenu = ref<InstanceType<typeof UserMenuContent> | null>(null);

const activeItemStyles =
    'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('ui.nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
]);

const rightNavItems = computed<NavItem[]>(() => [
    {
        title: t('ui.nav.repository'),
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: t('ui.nav.documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
]);

const toggleUserMenu = (event: Event): void => {
    userMenu.value?.toggle(event);
};
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Button
                        type="button"
                        text
                        rounded
                        class="mr-2"
                        :aria-label="t('ui.nav.navigation_menu')"
                        @click="mobileMenuVisible = true"
                    >
                        <MenuIcon class="size-5" />
                    </Button>
                </div>

                <Link :href="dashboard()" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <nav class="ml-10 flex h-full items-center gap-2">
                        <Link
                            v-for="item in mainNavItems"
                            :key="item.title"
                            :href="item.href"
                            class="relative flex h-9 items-center gap-2 rounded-md px-3 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            :class="whenCurrentUrl(item.href, activeItemStyles)"
                        >
                            <component
                                v-if="item.icon"
                                :is="item.icon"
                                class="size-4"
                            />
                            {{ item.title }}
                            <span
                                v-if="isCurrentUrl(item.href)"
                                class="absolute inset-x-0 -bottom-[13px] h-0.5 bg-foreground"
                            />
                        </Link>
                    </nav>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <Button
                            type="button"
                            text
                            rounded
                            class="group"
                            :aria-label="t('ui.nav.search')"
                        >
                            <Search
                                class="size-5 opacity-80 group-hover:opacity-100"
                            />
                        </Button>

                        <div class="hidden space-x-1 lg:flex">
                            <template
                                v-for="item in rightNavItems"
                                :key="item.title"
                            >
                                <Button
                                    as="a"
                                    :href="toUrl(item.href)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    text
                                    rounded
                                    class="group"
                                    :aria-label="item.title"
                                >
                                    <component
                                        :is="item.icon"
                                        class="size-5 opacity-80 group-hover:opacity-100"
                                    />
                                </Button>
                            </template>
                        </div>
                    </div>

                    <Button
                        type="button"
                        text
                        rounded
                        class="relative"
                        :aria-label="t('ui.user_menu.open')"
                        aria-haspopup="true"
                        aria-controls="header_user_menu"
                        @click="toggleUserMenu"
                    >
                        <Avatar
                            :image="auth.user.avatar || undefined"
                            :label="
                                auth.user.avatar
                                    ? undefined
                                    : getInitials(auth.user?.name)
                            "
                            :alt="auth.user.name"
                            shape="circle"
                        />
                    </Button>
                    <UserMenuContent
                        id="header_user_menu"
                        ref="userMenu"
                        :user="auth.user"
                        popup
                    />
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>

        <Drawer
            v-model:visible="mobileMenuVisible"
            position="left"
            :header="t('ui.nav.navigation_menu')"
            class="w-80"
        >
            <div class="flex h-full flex-col justify-between">
                <div class="space-y-6">
                    <AppLogoIcon
                        class="size-8 fill-current text-black dark:text-white"
                    />
                    <nav class="space-y-1">
                        <Link
                            v-for="item in mainNavItems"
                            :key="item.title"
                            :href="item.href"
                            class="flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium hover:bg-muted"
                            :class="whenCurrentUrl(item.href, activeItemStyles)"
                        >
                            <component
                                v-if="item.icon"
                                :is="item.icon"
                                class="size-5"
                            />
                            {{ item.title }}
                        </Link>
                    </nav>
                </div>
                <div class="flex flex-col gap-2">
                    <a
                        v-for="item in rightNavItems"
                        :key="item.title"
                        :href="toUrl(item.href)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium hover:bg-muted"
                    >
                        <component
                            v-if="item.icon"
                            :is="item.icon"
                            class="size-5"
                        />
                        <span>{{ item.title }}</span>
                    </a>
                    <div class="mt-4 border-t border-border pt-4">
                        <UserInfo :user="auth.user" :show-email="true" />
                    </div>
                </div>
            </div>
        </Drawer>
    </div>
</template>
