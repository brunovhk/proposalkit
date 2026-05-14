<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const { t } = useTranslations();

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('ui.nav.profile'),
        href: editProfile(),
    },
    {
        title: t('ui.nav.security'),
        href: editSecurity(),
    },
    {
        title: t('ui.nav.appearance'),
        href: editAppearance(),
    },
]);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="t('ui.settings.title')"
            :description="t('ui.settings.description')"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col gap-1"
                    :aria-label="t('ui.settings.title')"
                >
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :class="[
                            'rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-surface-200 dark:hover:bg-surface-700',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-surface-100 text-surface-900 dark:bg-surface-800 dark:text-surface-0'
                                : 'text-surface-500 dark:text-surface-400',
                        ]"
                    >
                        <span class="flex items-center gap-2">
                            <component
                                :is="item.icon"
                                v-if="item.icon"
                                class="h-4 w-4"
                            />
                            {{ item.title }}
                        </span>
                    </Link>
                </nav>
            </aside>

            <Divider class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
