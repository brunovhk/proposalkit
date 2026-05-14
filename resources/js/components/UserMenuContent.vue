<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';
import type Menu from 'primevue/menu';
import type { MenuItem } from 'primevue/menuitem';
import { computed, ref } from 'vue';
import UserInfo from '@/components/UserInfo.vue';
import { useTranslations } from '@/composables/useTranslations';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
    popup?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    popup: false,
});

const { t } = useTranslations();
const menu = ref<InstanceType<typeof Menu> | null>(null);

const handleLogout = (): void => {
    router.flushAll();
    router.post(logout().url);
};

const items = computed<MenuItem[]>(() => [
    {
        label: t('ui.user_menu.settings'),
        iconComponent: Settings,
        command: () => router.visit(edit().url),
    },
    {
        separator: true,
    },
    {
        label: t('ui.user_menu.logout'),
        iconComponent: LogOut,
        command: handleLogout,
        danger: true,
    },
]);

const toggle = (event: Event): void => {
    menu.value?.toggle(event);
};

defineExpose({ toggle });
</script>

<template>
    <Menu
        ref="menu"
        :model="items"
        :popup="popup"
        class="min-w-56"
        data-test="user-menu"
    >
        <template #start>
            <div class="flex items-center gap-3 px-3 py-3">
                <UserInfo :user="props.user" :show-email="true" />
            </div>
        </template>
        <template #item="{ item, props: itemProps }">
            <button
                type="button"
                v-bind="itemProps.action"
                class="flex w-full items-center gap-2"
                :class="{ 'text-red-600 dark:text-red-400': item.danger }"
                :data-test="item.danger ? 'logout-button' : undefined"
            >
                <component :is="item.iconComponent" class="size-4" />
                <span>{{ item.label }}</span>
            </button>
        </template>
    </Menu>
</template>
