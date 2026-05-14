import { BookOpen, FolderGit2, LayoutGrid } from 'lucide-vue-next';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

type UseNavigationReturn = {
    mainNavItems: ComputedRef<NavItem[]>;
    footerNavItems: ComputedRef<NavItem[]>;
};

export function useNavigation(): UseNavigationReturn {
    const { t } = useTranslations();
    const { isCurrentUrl } = useCurrentUrl();

    const mainNavItems = computed<NavItem[]>(() => {
        const dashboardRoute = dashboard();

        return [
            {
                title: t('ui.nav.dashboard'),
                href: dashboardRoute,
                icon: LayoutGrid,
                isActive: isCurrentUrl(dashboardRoute),
            },
        ];
    });

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

    return {
        mainNavItems,
        footerNavItems,
    };
}
