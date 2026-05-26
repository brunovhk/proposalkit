import {
    BriefcaseBusiness,
    FileText,
    LayoutGrid,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

type UseNavigationReturn = {
    mainNavItems: ComputedRef<NavItem[]>;
};

export function useNavigation(): UseNavigationReturn {
    const { t } = useTranslations();
    const { isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();

    const mainNavItems = computed<NavItem[]>(() => {
        const dashboardRoute = dashboard();
        const proposalsRoute = '/proposals';
        const servicesRoute = '/services';
        const clientsRoute = '/clients';

        return [
            {
                title: t('ui.nav.dashboard'),
                href: dashboardRoute,
                icon: LayoutGrid,
                isActive: isCurrentUrl(dashboardRoute),
            },
            {
                title: t('ui.nav.proposals'),
                href: proposalsRoute,
                icon: FileText,
                isActive: isCurrentOrParentUrl(proposalsRoute),
            },
            {
                title: t('ui.nav.services'),
                href: servicesRoute,
                icon: BriefcaseBusiness,
                isActive: isCurrentOrParentUrl(servicesRoute),
            },
            {
                title: t('ui.nav.clients'),
                href: clientsRoute,
                icon: UsersRound,
                isActive: isCurrentOrParentUrl(clientsRoute),
            },
        ];
    });

    return {
        mainNavItems,
    };
}
